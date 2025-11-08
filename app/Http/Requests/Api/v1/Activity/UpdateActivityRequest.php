<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1\Activity;

use App\Enums\Activity\ActivityVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['sometimes', 'string', Rule::enum(ActivityVisibility::class)],
            'completed_at' => ['nullable', 'date'],
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
            'title.max' => 'Título não pode ter mais de 255 caracteres',
            'description.max' => 'Descrição não pode ter mais de 5000 caracteres',
            'visibility.enum' => 'Visibilidade inválida',
            'completed_at.date' => 'Data de conclusão deve ser uma data válida',
            'distance_meters.numeric' => 'Distância deve ser um número válido',
            'distance_meters.min' => 'Distância não pode ser negativa',
            'avg_heart_rate.max' => 'Frequência cardíaca média não pode exceder 300 bpm',
            'max_heart_rate.max' => 'Frequência cardíaca máxima não pode exceder 300 bpm',
        ];
    }
}
