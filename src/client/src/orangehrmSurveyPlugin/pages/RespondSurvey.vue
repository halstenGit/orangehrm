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
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ survey.title || $t('survey.respond_to_survey') }}
      </oxd-text>
      <oxd-text
        v-if="survey.description"
        tag="p"
        class="orangehrm-survey-description"
      >
        {{ survey.description }}
      </oxd-text>

      <oxd-divider />

      <!-- Success message -->
      <div
        v-if="submitted"
        class="orangehrm-survey-success"
      >
        <oxd-text tag="p">{{ $t('survey.response_submitted_successfully') }}</oxd-text>
      </div>

      <oxd-form
        v-if="!submitted"
        :loading="isLoading"
        @submit-valid="onSubmit"
        @submit-invalid="onSubmitInvalid"
      >
        <div
          v-for="(question, index) in questions"
          :key="question.id"
          class="orangehrm-survey-respond-question"
        >
          <oxd-text tag="p" class="orangehrm-survey-respond-question-text">
            {{ index + 1 }}. {{ question.questionText }}
            <span v-if="question.isRequired" class="orangehrm-required-asterisk">*</span>
          </oxd-text>

          <!-- TEXT -->
          <template v-if="question.questionType === 'TEXT'">
            <oxd-input-field
              v-model="answers[question.id]"
              type="textarea"
              :label="$t('survey.your_answer')"
              :rules="question.isRequired ? [rules.required] : []"
            />
          </template>

          <!-- MULTIPLE_CHOICE -->
          <template v-else-if="question.questionType === 'MULTIPLE_CHOICE'">
            <multiple-choice-input
              v-model="answers[question.id]"
              :options="question.options"
              :question-id="question.id"
            />
            <oxd-text
              v-if="question.isRequired && validationErrors[question.id]"
              tag="p"
              class="orangehrm-survey-field-error"
            >
              {{ $t('general.required') }}
            </oxd-text>
          </template>

          <!-- SCALE_5 -->
          <template v-else-if="question.questionType === 'SCALE_5'">
            <scale-input
              v-model="answers[question.id]"
              :max="5"
              :question-id="question.id"
            />
            <oxd-text
              v-if="question.isRequired && validationErrors[question.id]"
              tag="p"
              class="orangehrm-survey-field-error"
            >
              {{ $t('general.required') }}
            </oxd-text>
          </template>

          <!-- SCALE_10 -->
          <template v-else-if="question.questionType === 'SCALE_10'">
            <scale-input
              v-model="answers[question.id]"
              :max="10"
              :question-id="question.id"
            />
            <oxd-text
              v-if="question.isRequired && validationErrors[question.id]"
              tag="p"
              class="orangehrm-survey-field-error"
            >
              {{ $t('general.required') }}
            </oxd-text>
          </template>

          <!-- YES_NO -->
          <template v-else-if="question.questionType === 'YES_NO'">
            <div class="orangehrm-survey-yesno">
              <label class="orangehrm-survey-radio-label">
                <input
                  v-model="answers[question.id]"
                  type="radio"
                  :name="`question_${question.id}`"
                  value="YES"
                />
                {{ $t('general.yes') }}
              </label>
              <label class="orangehrm-survey-radio-label">
                <input
                  v-model="answers[question.id]"
                  type="radio"
                  :name="`question_${question.id}`"
                  value="NO"
                />
                {{ $t('general.no') }}
              </label>
            </div>
            <oxd-text
              v-if="question.isRequired && validationErrors[question.id]"
              tag="p"
              class="orangehrm-survey-field-error"
            >
              {{ $t('general.required') }}
            </oxd-text>
          </template>

          <oxd-divider />
        </div>

        <oxd-form-actions>
          <oxd-button
            display-type="ghost"
            :label="$t('general.cancel')"
            @click="onCancel"
          />
          <oxd-button
            class="orangehrm-left-space"
            display-type="secondary"
            :label="$t('survey.submit_response')"
            type="submit"
          />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {required} from '@ohrm/core/util/validation/rules';
import ScaleInput from '@/orangehrmSurveyPlugin/components/ScaleInput.vue';
import MultipleChoiceInput from '@/orangehrmSurveyPlugin/components/MultipleChoiceInput.vue';

export default {
  components: {
    'scale-input': ScaleInput,
    'multiple-choice-input': MultipleChoiceInput,
  },

  props: {
    surveyId: {
      type: Number,
      required: true,
    },
  },

  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/survey/my-surveys/${props.surveyId}`,
    );
    const responseHttp = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/survey/my-surveys/${props.surveyId}/response`,
    );
    return {http, responseHttp};
  },

  data() {
    return {
      isLoading: false,
      submitted: false,
      survey: {
        title: '',
        description: '',
      },
      questions: [],
      answers: {},
      validationErrors: {},
      rules: {required},
    };
  },

  beforeMount() {
    this.loadSurvey();
  },

  methods: {
    loadSurvey() {
      this.isLoading = true;
      this.http
        .getAll()
        .then((response) => {
          const data = response.data?.data;
          if (data) {
            this.survey = {
              title: data.title || '',
              description: data.description || '',
            };
            this.questions = data.questions || [];
            // Initialize answers map
            this.questions.forEach((q) => {
              this.answers[q.id] = null;
            });
          }
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    validateRequiredAnswers() {
      this.validationErrors = {};
      let valid = true;
      this.questions.forEach((q) => {
        if (q.isRequired) {
          const answer = this.answers[q.id];
          if (answer === null || answer === undefined || answer === '') {
            this.validationErrors[q.id] = true;
            valid = false;
          }
        }
      });
      return valid;
    },
    onSubmit() {
      if (!this.validateRequiredAnswers()) {
        return;
      }
      this.isLoading = true;
      const payload = {
        answers: this.questions.map((q) => {
          const ans = this.answers[q.id];
          const item = {questionId: q.id};
          switch (q.questionType) {
            case 'TEXT':
              item.answerText = ans !== null && ans !== undefined ? String(ans) : null;
              break;
            case 'MULTIPLE_CHOICE':
              item.answerOptionId = ans !== null && ans !== undefined ? Number(ans) : null;
              break;
            case 'SCALE_5':
            case 'SCALE_10':
              item.answerScale = ans !== null && ans !== undefined ? Number(ans) : null;
              break;
            case 'YES_NO':
              item.answerYesNo = ans === 'YES' ? true : ans === 'NO' ? false : null;
              break;
          }
          return item;
        }),
      };
      this.responseHttp
        .create(payload)
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .then(() => {
          this.submitted = true;
          setTimeout(() => {
            navigate('/survey/mySurveys');
          }, 2000);
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onSubmitInvalid() {
      this.validateRequiredAnswers();
    },
    onCancel() {
      navigate('/survey/mySurveys');
    },
  },
};
</script>

<style scoped>
.orangehrm-survey-description {
  color: #666;
  margin-top: 0.5rem;
}
.orangehrm-survey-respond-question {
  margin-bottom: 1.5rem;
}
.orangehrm-survey-respond-question-text {
  font-weight: 600;
  margin-bottom: 0.75rem;
}
.orangehrm-required-asterisk {
  color: #e35252;
  margin-left: 0.25rem;
}
.orangehrm-survey-yesno {
  display: flex;
  gap: 2rem;
  margin-top: 0.5rem;
}
.orangehrm-survey-radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.95rem;
}
.orangehrm-survey-field-error {
  color: #e35252;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}
.orangehrm-survey-success {
  padding: 1.5rem;
  background: #e8f5e9;
  border-radius: 4px;
  text-align: center;
  color: #2e7d32;
  font-weight: 600;
  margin-bottom: 1rem;
}
</style>
