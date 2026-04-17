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
  <div class="orangehrm-scale-input">
    <label
      v-for="n in max"
      :key="n"
      class="orangehrm-scale-input__item"
      :class="{'orangehrm-scale-input__item--selected': modelValue === n}"
    >
      <input
        type="radio"
        :name="`scale_${questionId}`"
        :value="n"
        :checked="modelValue === n"
        class="orangehrm-scale-input__radio"
        @change="onSelect(n)"
      />
      <span class="orangehrm-scale-input__label">{{ n }}</span>
    </label>
  </div>
</template>

<script>
export default {
  props: {
    max: {
      type: Number,
      required: true,
      validator: (v) => v === 5 || v === 10,
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
    onSelect(value) {
      this.$emit('update:modelValue', value);
    },
  },
};
</script>

<style scoped>
.orangehrm-scale-input {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.orangehrm-scale-input__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  gap: 0.25rem;
}
.orangehrm-scale-input__radio {
  display: none;
}
.orangehrm-scale-input__label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border: 2px solid #c6c6c6;
  border-radius: 50%;
  font-size: 0.9rem;
  font-weight: 600;
  color: #555;
  transition: background-color 0.15s, border-color 0.15s, color 0.15s;
}
.orangehrm-scale-input__item--selected .orangehrm-scale-input__label {
  background-color: #ff7b1c;
  border-color: #ff7b1c;
  color: #fff;
}
.orangehrm-scale-input__item:hover .orangehrm-scale-input__label {
  border-color: #ff7b1c;
  color: #ff7b1c;
}
.orangehrm-scale-input__item--selected:hover .orangehrm-scale-input__label {
  color: #fff;
}
</style>
