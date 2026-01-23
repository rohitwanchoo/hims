<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationFormField extends Model
{
    protected $primaryKey = 'field_id';

    protected $fillable = [
        'form_id',
        'field_label',
        'field_key',
        'field_type',
        'field_options',
        'field_config',
        'default_value',
        'field_order',
        'is_required',
        'is_visible',
        'validation_rules',
        'section',
        'help_text',
    ];

    protected $casts = [
        'field_options' => 'array',
        'field_config' => 'array',
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'field_order' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(ConsultationForm::class, 'form_id', 'form_id');
    }

    /**
     * Get validation rules for this field
     */
    public function getValidationRule()
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Add type-specific rules
        switch ($this->field_type) {
            case 'email':
                $rules[] = 'email';
                break;
            case 'number':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'time':
                $rules[] = 'date_format:H:i';
                break;
            case 'datetime':
                $rules[] = 'date';
                break;
        }

        // Add custom validation rules if defined
        if ($this->validation_rules) {
            $customRules = explode('|', $this->validation_rules);
            $rules = array_merge($rules, $customRules);
        }

        return implode('|', $rules);
    }

    /**
     * Get field options as array
     */
    public function getOptionsArray()
    {
        if (!$this->field_options) {
            return [];
        }

        // If options are already an array
        if (is_array($this->field_options)) {
            return $this->field_options;
        }

        return [];
    }
}
