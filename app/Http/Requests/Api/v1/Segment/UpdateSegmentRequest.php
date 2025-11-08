<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\v1\Segment;

use App\Enums\Segment\SegmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', Rule::enum(SegmentType::class)],
            'distance_meters' => ['sometimes', 'numeric', 'min:50', 'max:100000'],
            'avg_grade_percent' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'max_grade_percent' => ['nullable', 'numeric', 'min:-50', 'max:50'],
            'elevation_gain' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'is_hazardous' => ['nullable', 'boolean'],
            'route' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Nome não pode ter mais de 255 caracteres',
            'description.max' => 'Descrição não pode ter mais de 5000 caracteres',
            'type.enum' => 'Tipo de segmento inválido',
            'distance_meters.min' => 'Distância mínima é 50 metros',
            'distance_meters.max' => 'Distância máxima é 100 km',
            'avg_grade_percent.min' => 'Inclinação média não pode ser menor que -50%',
            'avg_grade_percent.max' => 'Inclinação média não pode ser maior que 50%',
            'max_grade_percent.min' => 'Inclinação máxima não pode ser menor que -50%',
            'max_grade_percent.max' => 'Inclinação máxima não pode ser maior que 50%',
            'elevation_gain.min' => 'Ganho de elevação não pode ser negativo',
            'elevation_gain.max' => 'Ganho de elevação não pode exceder 10000m',
        ];
    }
}
