<template>
  <div class="dynamic-field">
    <label v-if="field.field_label" class="form-label">
      {{ field.field_label }}
      <span v-if="field.is_required" class="text-danger">*</span>
    </label>

    <!-- Text Input -->
    <input
      v-if="field.field_type === 'text'"
      type="text"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :placeholder="field.placeholder"
      :required="field.is_required"
    />

    <!-- Textarea -->
    <textarea
      v-else-if="field.field_type === 'textarea'"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :rows="field.field_config?.rows || 3"
      :placeholder="field.placeholder"
      :required="field.is_required"
    ></textarea>

    <!-- Number -->
    <input
      v-else-if="field.field_type === 'number'"
      type="number"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :placeholder="field.placeholder"
      :min="field.field_config?.min"
      :max="field.field_config?.max"
      :step="field.field_config?.step || 'any'"
      :required="field.is_required"
    />

    <!-- Email -->
    <input
      v-else-if="field.field_type === 'email'"
      type="email"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :placeholder="field.placeholder"
      :required="field.is_required"
    />

    <!-- Phone -->
    <input
      v-else-if="field.field_type === 'phone'"
      type="tel"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :placeholder="field.placeholder || '+1 (555) 123-4567'"
      :required="field.is_required"
    />

    <!-- Date -->
    <input
      v-else-if="field.field_type === 'date'"
      type="date"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :required="field.is_required"
    />

    <!-- Time -->
    <input
      v-else-if="field.field_type === 'time'"
      type="time"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :required="field.is_required"
    />

    <!-- Datetime -->
    <input
      v-else-if="field.field_type === 'datetime'"
      type="datetime-local"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="form-control"
      :required="field.is_required"
    />

    <!-- Dropdown/Select -->
    <select
      v-else-if="field.field_type === 'dropdown'"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      class="form-select"
      :required="field.is_required"
    >
      <option value="">-- Select {{ field.field_label }} --</option>
      <option
        v-for="(option, idx) in field.field_options"
        :key="idx"
        :value="option.value || option"
      >
        {{ option.label || option }}
      </option>
    </select>

    <!-- Radio Buttons -->
    <div v-else-if="field.field_type === 'radio'" class="radio-group">
      <div
        v-for="(option, idx) in field.field_options"
        :key="idx"
        class="form-check"
      >
        <input
          :id="`${field.field_key}_${idx}`"
          type="radio"
          :name="field.field_key"
          :value="option.value || option"
          :checked="modelValue === (option.value || option)"
          @change="$emit('update:modelValue', option.value || option)"
          class="form-check-input"
          :required="field.is_required"
        />
        <label :for="`${field.field_key}_${idx}`" class="form-check-label">
          {{ option.label || option }}
        </label>
      </div>
    </div>

    <!-- Checkboxes -->
    <div v-else-if="field.field_type === 'checkbox'" class="checkbox-group">
      <div
        v-for="(option, idx) in field.field_options"
        :key="idx"
        class="form-check"
      >
        <input
          :id="`${field.field_key}_${idx}`"
          type="checkbox"
          :value="option.value || option"
          :checked="Array.isArray(modelValue) && modelValue.includes(option.value || option)"
          @change="handleCheckboxChange(option.value || option)"
          class="form-check-input"
        />
        <label :for="`${field.field_key}_${idx}`" class="form-check-label">
          {{ option.label || option }}
        </label>
      </div>
    </div>

    <!-- File Upload -->
    <div v-else-if="field.field_type === 'file'" class="file-upload">
      <input
        type="file"
        @change="handleFileChange"
        class="form-control"
        :accept="field.field_config?.accept"
        :multiple="field.field_config?.multiple"
        :required="field.is_required"
      />
      <div v-if="modelValue && typeof modelValue === 'string'" class="mt-2">
        <small class="text-muted">
          <i class="bi bi-paperclip me-1"></i>
          Current file: {{ getFileName(modelValue) }}
          <a :href="modelValue" target="_blank" class="ms-2">
            <i class="bi bi-eye"></i> View
          </a>
        </small>
      </div>
    </div>

    <!-- Unsupported field type -->
    <div v-else class="alert alert-warning">
      Unsupported field type: {{ field.field_type }}
    </div>

    <!-- Help Text -->
    <small v-if="field.help_text" class="form-text text-muted">
      {{ field.help_text }}
    </small>

    <!-- Validation Error -->
    <div v-if="error" class="invalid-feedback d-block">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Array, Boolean, Object],
    default: null,
  },
  error: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue']);

const handleCheckboxChange = (value) => {
  let currentValue = Array.isArray(props.modelValue) ? [...props.modelValue] : [];

  if (currentValue.includes(value)) {
    currentValue = currentValue.filter(v => v !== value);
  } else {
    currentValue.push(value);
  }

  emit('update:modelValue', currentValue);
};

const handleFileChange = (event) => {
  const files = event.target.files;

  if (files.length === 0) {
    emit('update:modelValue', null);
    return;
  }

  // If multiple files allowed, emit array of files
  if (props.field.field_config?.multiple) {
    emit('update:modelValue', Array.from(files));
  } else {
    // Single file
    emit('update:modelValue', files[0]);
  }
};

const getFileName = (filePath) => {
  if (!filePath) return '';
  return filePath.split('/').pop();
};
</script>

<style scoped>
.dynamic-field {
  margin-bottom: 0;
}

.form-label {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.radio-group .form-check,
.checkbox-group .form-check {
  margin-bottom: 0.25rem;
}

.invalid-feedback {
  font-size: 0.875rem;
}
</style>
