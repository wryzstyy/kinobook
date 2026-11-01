<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AiController extends Controller
{
    // Админ: краткое описание без спойлеров
    public function movieDescription(Request $request, AiService $ai): JsonResponse
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'genres'           => ['nullable', 'array', 'max:10'],
            'genres.*'         => ['string', 'max:50'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $system = 'Ты редактор афиши кинотеатра. Пишешь короткие описания фильмов на русском языке без спойлеров.';

        $prompt = "Фильм: {$data['title']}\n"
            .'Жанры: '.implode(', ', $data['genres'] ?? ['не указаны'])."\n"
            .'Длительность: '.($data['duration_minutes'] ?? 'не указана')." мин\n"
            .'Заметки редактора: '.($data['notes'] ?? 'нет')."\n\n"
            .'Напиши описание из 2-3 предложений, не больше 300 символов. '
            .'Не раскрывай повороты сюжета, концовку, судьбу героев и личности персонажей. '
            .'Не выдумывай факты, имена актёров и награды, которых нет в данных выше. '
            .'Если о сюжете сказать нечего, опиши атмосферу и жанр. '
            .'Верни только текст описания, без кавычек и пояснений.';

        try {
            $text = $ai->complete($system, $prompt, 400);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json(['description' => $text]);
    }

    // Зритель: рекомендации по истории покупок
    public function recommendations(Request $request, AiService $ai): JsonResponse
    {
        $user = $request->user();

        // Фильмы, на которые зритель покупал билеты
        $watched = Movie::with('genres')
            ->whereHas('screenings.tickets.order', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $genreWeights = $watched->flatMap(fn ($m) => $m->genres)->countBy(fn ($g) => $g->id);

        // Сколько билетов продано на каждый фильм (для популярности)
        $sold = DB::table('tickets')
            ->join('screenings', 'screenings.id', '=', 'tickets.screening_id')
            ->select('screenings.movie_id', DB::raw('count(*) as c'))
            ->groupBy('screenings.movie_id')
            ->pluck('c', 'movie_id');

        // Кандидаты: есть будущие сеансы и фильм ещё не куплен
        $top = Movie::with('genres')
            ->whereHas('screenings', fn ($q) => $q->where('starts_at', '>', now()))
            ->whereNotIn('id', $watched->pluck('id'))
            ->get()
            ->map(function ($m) use ($genreWeights, $sold) {
                $m->score = $m->genres->sum(fn ($g) => $genreWeights[$g->id] ?? 0) * 10 + ($sold[$m->id] ?? 0);

                return $m;
            })
            ->sortByDesc('score')
            ->values()
            ->take(6);

        if ($top->isEmpty()) {
            return response()->json(['source' => 'fallback', 'data' => []]);
        }

        $picks = null;
        $source = 'fallback';

        if ($ai->isConfigured() && $top->count() > 1) {
            try {
                $picks = $this->askAi($ai, $watched, $top);
                $source = 'ai';
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (empty($picks)) {
            $source = 'fallback';
            $picks = $top->take(3)->map(fn ($m) => [
                'movie_id' => $m->id,
                'reason'   => $watched->isEmpty()
                    ? 'Популярный фильм в прокате.'
                    : 'Подходит по жанрам к фильмам, которые вы уже смотрели.',
            ])->all();
        }

        $byId = $top->keyBy('id');

        $data = collect($picks)
            ->filter(fn ($p) => $byId->has($p['movie_id']))
            ->map(fn ($p) => [
                'movie'  => (new MovieResource($byId[$p['movie_id']]))->resolve(),
                'reason' => $p['reason'],
            ])
            ->values();

        return response()->json(['source' => $source, 'data' => $data]);
    }

    private function askAi(AiService $ai, $watched, $top): array
    {
        $history = $watched->isEmpty()
            ? 'покупок пока нет'
            : $watched->map(fn ($m) => "- {$m->title} (".$m->genres->pluck('name')->join(', ').')')->join("\n");

        $list = $top->map(fn ($m) => "{$m->id}: {$m->title} [".$m->genres->pluck('name')->join(', ')."], {$m->age_rating}")
            ->join("\n");

        $system = 'Ты рекомендательная система кинотеатра. Отвечай только валидным JSON, без пояснений и без markdown.';

        $prompt = "История зрителя:\n{$history}\n\nДоступные фильмы (id: название):\n{$list}\n\n"
            .'Выбери до 3 фильмов из списка, которые лучше всего подойдут зрителю. '
            .'Верни JSON-массив вида [{"movie_id": 1, "reason": "одно короткое предложение по-русски без спойлеров"}]. '
            .'Используй только id из списка.';

        $text = $ai->complete($system, $prompt, 500);
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('ИИ вернул неверный формат.');
        }

        return array_values(array_filter(array_map(
            fn ($p) => isset($p['movie_id'], $p['reason'])
                ? ['movie_id' => (int) $p['movie_id'], 'reason' => (string) $p['reason']]
                : null,
            $decoded
        )));
    }
}