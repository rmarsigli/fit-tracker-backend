<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1\Activity;

use App\Enums\Activity\ActivityType;
use App\Enums\Activity\ActivityVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(ActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'string', Rule::enum(ActivityVisibility::class)],
            'started_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date', 'after:started_at'],
            'distance_meters' => ['nullable', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'moving_time_seconds' => ['nullable', 'integer', 'min:0'],
            'elevation_gain' => ['nullable', 'numeric', 'min:0'],
            'elevation_loss' => ['nullable', 'numeric', 'min:0'],
            'avg_speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'max_speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'avg_heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'max_heart_rate' => ['nullable', 'integer', 'min:0', 'max:300'],
            'calories' => ['nullable', 'integer', 'min:0'],
            'avg_cadence' => ['nullable', 'integer', 'min:0'],
            'splits' => ['nullable', 'array'],
            'weather' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Tipo de atividade é obrigatório',
            'type.enum' => 'Tipo de atividade inválido',
            'title.required' => 'Título é obrigatório',
            'title.max' => 'Título não pode ter mais de 255 caracteres',
            'description.max' => 'Descrição não pode ter mais de 5000 caracteres',
            'visibility.enum' => 'Visibilidade inválida',
            'started_at.required' => 'Data de início é obrigatória',
            'started_at.date' => 'Data de início deve ser uma data válida',
            'completed_at.date' => 'Data de conclusão deve ser uma data válida',
            'completed_at.after' => 'Data de conclusão deve ser posterior à data de início',
            'distance_meters.numeric' => 'Distância deve ser um número válido',
            'distance_meters.min' => 'Distância não pode ser negativa',
            'avg_heart_rate.max' => 'Frequência cardíaca média não pode exceder 300 bpm',
            'max_heart_rate.max' => 'Frequência cardíaca máxima não pode exceder 300 bpm',
        ];
    }
}
