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
  <div class="orangehrm-multiple-choice-input">
    <label
      v-for="option in options"
      :key="option.id"
      class="orangehrm-multiple-choice-input__option"
    >
      <input
        type="radio"
        :name="`mc_${questionId}`"
        :value="option.id"
        :checked="modelValue === option.id"
        class="orangehrm-multiple-choice-input__radio"
        @change="onSelect(option.id)"
      />
      <span class="orangehrm-multiple-choice-input__label">
        {{ option.optionText }}
      </span>
    </label>
  </div>
</template>

<script>
export default {
  props: {
    options: {
      type: Array,
      required: true,
      default: () => [],
    },
    modelValue: {
      type: Number,
      default: null,
    },
    questionId: {
      type: Number,
      required: true,
    },
  },

  emits: ['update:modelValue'],

  methods: {
    onSelect(optionId) {
      this.$emit('update:modelValue', optionId);
    },
  },
};
</script>

<style scoped>
.orangehrm-multiple-choice-input {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.orangehrm-multiple-choice-input__option {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  cursor: pointer;
  padding: 0.4rem 0;
}
.orangehrm-multiple-choice-input__radio {
  width: 1.1rem;
  height: 1.1rem;
  accent-color: #ff7b1c;
  cursor: pointer;
}
.orangehrm-multiple-choice-input__label {
  font-size: 0.95rem;
  color: #333;
}
</style>
