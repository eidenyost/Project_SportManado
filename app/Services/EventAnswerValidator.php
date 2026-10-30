<?php

namespace App\Services;

use App\Models\Event;

class EventAnswerValidator
{
    public function validate(Event $event, mixed $submittedAnswers): array
    {
        $fields = is_array($event->custom_fields) ? $event->custom_fields : [];
        $errors = [];

        if (!is_array($submittedAnswers)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'custom_answers' => 'Jawaban formulir harus dikirim dalam format yang benar.',
            ]);
        }

        $knownLabels = [];
        foreach ($fields as $field) {
            if (is_array($field) && is_string($field['label'] ?? null) && $field['label'] !== '') {
                $knownLabels[$field['label']] = true;
            }
        }

        foreach (array_keys($submittedAnswers) as $label) {
            if (!is_string($label) || !isset($knownLabels[$label])) {
                $errors['custom_answers'] = 'Formulir berisi pertanyaan yang tidak tersedia.';
                break;
            }
        }

        $customAnswers = [];
        foreach ($fields as $index => $field) {
            if (!is_array($field) || !is_string($field['label'] ?? null) || $field['label'] === '') {
                continue;
            }

            $label = $field['label'];
            $required = !array_key_exists('required', $field)
                || filter_var($field['required'], FILTER_VALIDATE_BOOLEAN);
            $answer = $submittedAnswers[$label] ?? null;

            if ($answer === null || $answer === '') {
                if ($required) {
                    $errors["custom_answers.{$index}"] = "Kolom '{$label}' wajib diisi.";
                }
                continue;
            }

            if (!is_string($answer)) {
                $errors["custom_answers.{$index}"] = "Jawaban untuk '{$label}' harus berupa teks.";
                continue;
            }

            $answer = trim($answer);
            if ($answer === '') {
                if ($required) {
                    $errors["custom_answers.{$index}"] = "Kolom '{$label}' wajib diisi.";
                }
                continue;
            }

            $maximumLength = ($field['type'] ?? 'text') === 'textarea' ? 2000 : 500;
            if (mb_strlen($answer) > $maximumLength) {
                $errors["custom_answers.{$index}"] = "Jawaban untuk '{$label}' terlalu panjang.";
                continue;
            }

            if (($field['type'] ?? 'text') === 'radio') {
                $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                if (!in_array($answer, $options, true)) {
                    $errors["custom_answers.{$index}"] = "Pilihan untuk '{$label}' tidak valid.";
                    continue;
                }
            }

            $customAnswers[$label] = $answer;
        }

        if ($errors !== []) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        return $customAnswers;
        }
}
