<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'image_url' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,pending,published',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }


    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề không được để trống.',
            'content.required' => 'Nội dung bài viết không được để trống.',
            'category_id.exists' => 'Chuyên mục được chọn không hợp lệ.',
            'status.in' => 'Trạng thái bài viết không hợp lệ.',
            'tags.*.exists' => 'Một hoặc nhiều hashtag không tồn tại trong hệ thống.',
        ];
    }
}
