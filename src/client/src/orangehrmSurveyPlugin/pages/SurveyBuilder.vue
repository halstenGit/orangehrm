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
      <!-- Header -->
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('survey.survey_builder') }}
        </oxd-text>
        <div class="orangehrm-header-container--actions">
          <oxd-text tag="p" class="orangehrm-survey-question-count">
            {{ $t('survey.questions_count', {count: questions.length}) }}
          </oxd-text>
          <oxd-button
            class="orangehrm-left-space"
            display-type="secondary"
            :label="$t('survey.publish_survey')"
            :disabled="isLoading || questions.length === 0"
            @click="onPublishSurvey"
          />
        </div>
      </div>

      <oxd-divider />

      <!-- Question list -->
      <div v-if="questions.length > 0" class="orangehrm-survey-questions">
        <div
          v-for="(question, index) in questions"
          :key="question.id"
          class="orangehrm-survey-question-item orangehrm-paper-container"
        >
          <div class="orangehrm-survey-question-header">
            <oxd-text tag="p" class="orangehrm-survey-question-number">
              {{ index + 1 }}.
            </oxd-text>
            <oxd-text tag="p" class="orangehrm-survey-question-text">
              {{ question.questionText }}
              <span v-if="question.required" class="orangehrm-required-text">*</span>
            </oxd-text>
            <oxd-text tag="span" class="orangehrm-survey-question-type">
              {{ getQuestionTypeLabel(question.questionType) }}
            </oxd-text>
          </div>
          <div
            v-if="question.questionType === 'MULTIPLE_CHOICE' && question.options"
            class="orangehrm-survey-options"
          >
            <oxd-text
              v-for="option in question.options"
              :key="option.id"
              tag="p"
              class="orangehrm-survey-option"
            >
              - {{ option.optionText }}
            </oxd-text>
          </div>
          <div class="orangehrm-survey-question-actions">
            <oxd-icon-button
              name="pencil-fill"
              @click="onEditQuestion(question)"
            />
            <oxd-icon-button
              name="trash"
              class="orangehrm-left-space"
              @click="onDeleteQuestion(question)"
            />
          </div>
        </div>
      </div>

      <div
        v-if="questions.length === 0 && !isLoading && !showAddForm"
        class="orangehrm-survey-empty"
      >
        <oxd-text tag="p">{{ $t('survey.no_questions_added') }}</oxd-text>
      </div>

      <oxd-divider v-if="questions.length > 0" />

      <!-- Add/Edit question form -->
      <div v-if="showAddForm" class="orangehrm-survey-add-question">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ editingQuestion ? $t('survey.edit_question') : $t('survey.add_question') }}
        </oxd-text>
        <oxd-divider />
        <oxd-form :loading="isSavingQuestion" @submit-valid="onSaveQuestion">
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="questionForm.questionText"
                type="textarea"
                :label="$t('survey.question_text')"
                :rules="rules.questionText"
                required
              />
            </oxd-grid-item>

            <oxd-grid-item>
              <question-type-selector
                v-model="questionForm.questionType"
                :label="$t('survey.question_type')"
                :rules="rules.questionType"
                required
              />
            </oxd-grid-item>

            <oxd-grid-item>
              <div class="orangehrm-sm-field">
                <oxd-text tag="p" class="orangehrm-sm-field-label">
                  {{ $t('survey.required') }}
                </oxd-text>
                <oxd-switch-input v-model="questionForm.required" />
              </div>
            </oxd-grid-item>
          </oxd-grid>

          <!-- Multiple choice options -->
          <div
            v-if="questionForm.questionType === 'MULTIPLE_CHOICE'"
            class="orangehrm-survey-options-builder"
          >
            <oxd-divider />
            <oxd-text tag="p" class="orangehrm-survey-options-label">
              {{ $t('survey.answer_options') }}
            </oxd-text>
            <div
              v-for="(option, idx) in questionForm.options"
              :key="idx"
              class="orangehrm-survey-option-row"
            >
              <oxd-input-field
                v-model="questionForm.options[idx]"
                :label="`${$t('survey.option')} ${idx + 1}`"
                :rules="rules.optionText"
              />
              <oxd-icon-button
                v-if="questionForm.options.length > 1"
                name="trash"
                class="orangehrm-left-space"
                @click="removeOption(idx)"
              />
            </div>
            <oxd-button
              display-type="ghost"
              icon-name="plus"
              :label="$t('survey.add_option')"
              @click="addOption"
            />
          </div>

          <oxd-divider />
          <oxd-form-actions>
            <oxd-button
              display-type="ghost"
              :label="$t('general.cancel')"
              @click="onCancelAddQuestion"
            />
            <submit-button />
          </oxd-form-actions>
        </oxd-form>
      </div>

      <!-- Add question button -->
      <div v-if="!showAddForm" class="orangehrm-survey-add-btn">
        <oxd-button
          display-type="secondary"
          icon-name="plus"
          :label="$t('survey.add_question')"
          @click="onClickAddQuestion"
        />
      </div>

      <oxd-divider />

      <div class="orangehrm-form-actions">
        <oxd-button
          display-type="ghost"
          :label="$t('general.back')"
          @click="onBack"
        />
      </div>
    </div>
  </div>
  <delete-confirmation ref="deleteDialog"></delete-confirmation>
</template>

<script>
import {OxdSwitchInput} from '@ohrm/oxd';
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {required, shouldNotExceedCharLength} from '@ohrm/core/util/validation/rules';
import QuestionTypeSelector from '@/orangehrmSurveyPlugin/components/QuestionTypeSelector.vue';
import DeleteConfirmationDialog from '@ohrm/components/dialogs/DeleteConfirmationDialog.vue';

const initialQuestionForm = () => ({
  questionText: '',
  questionType: null,
  required: false,
  options: ['', ''],
});

const QUESTION_TYPE_LABELS = {
  TEXT: 'Text',
  MULTIPLE_CHOICE: 'Multiple Choice',
  SCALE_5: 'Scale (1-5)',
  SCALE_10: 'Scale (1-10)',
  YES_NO: 'Yes / No',
};

export default {
  components: {
    'oxd-switch-input': OxdSwitchInput,
    'question-type-selector': QuestionTypeSelector,
    'delete-confirmation': DeleteConfirmationDialog,
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
      `/api/v2/survey/surveys/${props.surveyId}/questions`,
    );
    const surveyHttp = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/survey/surveys',
    );
    return {http, surveyHttp};
  },

  data() {
    return {
      isLoading: false,
      isSavingQuestion: false,
      questions: [],
      showAddForm: false,
      editingQuestion: null,
      questionForm: initialQuestionForm(),
      rules: {
        questionText: [required, shouldNotExceedCharLength(500)],
        questionType: [required],
        optionText: [required, shouldNotExceedCharLength(200)],
      },
    };
  },

  beforeMount() {
    this.loadQuestions();
  },

  methods: {
    loadQuestions() {
      this.isLoading = true;
      this.http
        .getAll()
        .then((response) => {
          this.questions = response.data?.data || [];
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    getQuestionTypeLabel(type) {
      return QUESTION_TYPE_LABELS[type] || type;
    },
    onClickAddQuestion() {
      this.editingQuestion = null;
      this.questionForm = initialQuestionForm();
      this.showAddForm = true;
    },
    onEditQuestion(question) {
      this.editingQuestion = question;
      this.questionForm = {
        questionText: question.questionText,
        questionType: question.questionType,
        required: question.required,
        options:
          question.options && question.options.length > 0
            ? question.options.map((o) => o.optionText)
            : ['', ''],
      };
      this.showAddForm = true;
    },
    onCancelAddQuestion() {
      this.showAddForm = false;
      this.editingQuestion = null;
      this.questionForm = initialQuestionForm();
    },
    addOption() {
      this.questionForm.options.push('');
    },
    removeOption(index) {
      this.questionForm.options.splice(index, 1);
    },
    onSaveQuestion() {
      this.isSavingQuestion = true;
      const payload = {
        questionText: this.questionForm.questionText.trim(),
        questionType: this.questionForm.questionType,
        required: this.questionForm.required,
        options:
          this.questionForm.questionType === 'MULTIPLE_CHOICE'
            ? this.questionForm.options
                .filter((o) => o.trim() !== '')
                .map((o) => ({optionText: o.trim()}))
            : [],
      };

      const request = this.editingQuestion
        ? this.http.update(this.editingQuestion.id, payload)
        : this.http.create(payload);

      request
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .then(() => {
          this.onCancelAddQuestion();
          this.loadQuestions();
        })
        .finally(() => {
          this.isSavingQuestion = false;
        });
    },
    onDeleteQuestion(question) {
      this.$refs.deleteDialog.showDialog().then((confirmation) => {
        if (confirmation === 'ok') {
          this.isLoading = true;
          this.http
            .deleteAll({ids: [question.id]})
            .then(() => {
              return this.$toast.deleteSuccess();
            })
            .then(() => {
              this.loadQuestions();
            })
            .finally(() => {
              this.isLoading = false;
            });
        }
      });
    },
    onPublishSurvey() {
      this.isLoading = true;
      this.surveyHttp
        .request({
          method: 'PUT',
          url: `${window.appGlobal.baseUrl}/api/v2/survey/surveys/${this.surveyId}/action`,
          data: {action: 'publish'},
        })
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .then(() => {
          navigate('/survey/viewSurveys');
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    onBack() {
      navigate('/survey/viewSurveys');
    },
  },
};
</script>

<style scoped>
.orangehrm-survey-question-item {
  margin-bottom: 1rem;
  padding: 1rem;
}
.orangehrm-survey-question-header {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.orangehrm-survey-question-number {
  font-weight: 600;
  min-width: 1.5rem;
}
.orangehrm-survey-question-text {
  flex: 1;
}
.orangehrm-survey-question-type {
  font-size: 0.8rem;
  color: #888;
  background: #f0f0f0;
  padding: 0.1rem 0.5rem;
  border-radius: 8px;
}
.orangehrm-survey-question-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}
.orangehrm-survey-options {
  margin: 0.5rem 0 0 2rem;
}
.orangehrm-survey-option {
  color: #555;
  font-size: 0.9rem;
}
.orangehrm-survey-options-builder {
  margin-top: 1rem;
}
.orangehrm-survey-options-label {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.orangehrm-survey-option-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}
.orangehrm-survey-add-btn {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-start;
}
.orangehrm-survey-empty {
  text-align: center;
  padding: 2rem;
  color: #888;
}
.orangehrm-survey-question-count {
  font-size: 0.9rem;
  color: #666;
}
.orangehrm-header-container--actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.orangehrm-required-text {
  color: #e35252;
}
.orangehrm-form-actions {
  display: flex;
  justify-content: flex-end;
}
</style>
