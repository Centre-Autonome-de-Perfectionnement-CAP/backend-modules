<?php

namespace App\Modules\Cours\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCourseElementResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_element_id' => 'required|exists:course_elements,id',
            'file' => 'required|file|mimes:pdf,pptx,ppt,docx,doc,xlsx,xls,mp4,mp3|max:51200',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pedagogical_type' => 'required|in:syllabus,cours,td,tp,examen', // nouveau
            'is_public' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'course_element_id.required' => 'L\'élément de cours est obligatoire.',
            'course_element_id.exists' => 'L\'élément de cours n\'existe pas.',
            'file.required' => 'Le fichier est obligatoire.',
            'file.mimes' => 'Le fichier doit être de type: pdf, pptx, docx, xlsx, mp4, mp3.',
            'file.max' => 'Le fichier ne peut pas dépasser 50 Mo.',
            'title.required' => 'Le titre est obligatoire.',
            'resource_type.required' => 'Le type de ressource est obligatoire.',
        ];
    }
}
