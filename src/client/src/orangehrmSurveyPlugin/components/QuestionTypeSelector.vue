<!--
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */
 -->

<template>
  <oxd-input-field
    type="select"
    :model-value="selectedOption"
    :label="label"
    :options="questionTypeOptions"
    :rules="rules"
    :required="required"
    @update:model-value="onUpdate"
  />
</template>

<script>
export default {
  props: {
    modelValue: {
      type: String,
      default: null,
    },
    label: {
      type: String,
      default: '',
    },
    rules: {
      type: Array,
      default: () => [],
    },
    required: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['update:modelValue'],

  computed: {
    questionTypeOptions() {
      return [
        {id: 'TEXT', label: this.$t('survey.type_text')},
        {id: 'MULTIPLE_CHOICE', label: this.$t('survey.type_multiple_choice')},
        {id: 'SCALE_5', label: this.$t('survey.type_scale_5')},
        {id: 'SCALE_10', label: this.$t('survey.type_scale_10')},
        {id: 'YES_NO', label: this.$t('survey.type_yes_no')},
      ];
    },
    selectedOption() {
      if (!this.modelValue) return null;
      return (
        this.questionTypeOptions.find((opt) => opt.id === this.modelValue) || null
      );
    },
  },

  methods: {
    onUpdate(value) {
      this.$emit('update:modelValue', value ? value.id : null);
    },
  },
};
</script>
