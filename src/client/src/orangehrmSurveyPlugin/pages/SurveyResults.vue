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
      <!-- Survey header info -->
      <div class="orangehrm-header-container">
        <div>
          <oxd-text tag="h6" class="orangehrm-main-title">
            {{ $t('survey.survey_results') }}
          </oxd-text>
          <oxd-text v-if="surveyTitle" tag="p" class="orangehrm-survey-subtitle">
            {{ surveyTitle }}
          </oxd-text>
        </div>
        <oxd-text tag="p" class="orangehrm-survey-response-count">
          {{ $t('survey.total_responses', {count: responseCount}) }}
        </oxd-text>
      </div>

      <oxd-divider />

      <div v-if="isLoading" class="orangehrm-survey-loading">
        <oxd-text tag="p">{{ $t('general.loading') }}</oxd-text>
      </div>

      <div
        v-else-if="!isLoading && questionResults.length === 0"
        class="orangehrm-survey-empty"
      >
        <oxd-text tag="p">{{ $t('survey.no_responses_yet') }}</oxd-text>
      </div>

      <!-- Per-question results -->
      <div
        v-for="(question, index) in questionResults"
        :key="question.questionId"
        class="orangehrm-survey-result-section orangehrm-paper-container"
      >
        <oxd-text tag="p" class="orangehrm-survey-question-label">
          {{ index + 1 }}. {{ question.questionText }}
          <span class="orangehrm-survey-question-type-badge">
            {{ getQuestionTypeLabel(question.questionType) }}
          </span>
        </oxd-text>

        <oxd-divider />

        <!-- TEXT answers -->
        <template v-if="question.questionType === 'TEXT'">
          <div
            v-if="question.answers && question.answers.length > 0"
            class="orangehrm-survey-text-answers"
          >
            <div
              v-for="(answer, aIdx) in question.answers"
              :key="aIdx"
              class="orangehrm-survey-text-answer"
            >
              <oxd-text tag="p">"{{ answer }}"</oxd-text>
            </div>
          </div>
          <oxd-text v-else tag="p" class="orangehrm-survey-no-answers">
            {{ $t('survey.no_answers') }}
          </oxd-text>
        </template>

        <!-- MULTIPLE_CHOICE bar -->
        <template v-else-if="question.questionType === 'MULTIPLE_CHOICE'">
          <div
            v-for="option in question.options"
            :key="option.optionId"
            class="orangehrm-survey-bar-row"
          >
            <oxd-text tag="p" class="orangehrm-survey-bar-label">
              {{ option.optionText }}
            </oxd-text>
            <div class="orangehrm-survey-bar-track">
              <div
                class="orangehrm-survey-bar-fill"
                :style="{width: getOptionPercent(option.count, responseCount) + '%'}"
              ></div>
            </div>
            <oxd-text tag="span" class="orangehrm-survey-bar-count">
              {{ option.count }} ({{ getOptionPercent(option.count, responseCount) }}%)
            </oxd-text>
          </div>
        </template>

        <!-- SCALE bars -->
        <template
          v-else-if="
            question.questionType === 'SCALE_5' ||
            question.questionType === 'SCALE_10'
          "
        >
          <oxd-text tag="p" class="orangehrm-survey-avg">
            {{ $t('survey.average') }}: <strong>{{ question.average }}</strong>
          </oxd-text>
          <div
            v-for="entry in question.distribution"
            :key="entry.value"
            class="orangehrm-survey-bar-row"
          >
            <oxd-text tag="p" class="orangehrm-survey-bar-label">
              {{ entry.value }}
            </oxd-text>
            <div class="orangehrm-survey-bar-track">
              <div
                class="orangehrm-survey-bar-fill"
                :style="{width: getOptionPercent(entry.count, responseCount) + '%'}"
              ></div>
            </div>
            <oxd-text tag="span" class="orangehrm-survey-bar-count">
              {{ entry.count }} ({{ getOptionPercent(entry.count, responseCount) }}%)
            </oxd-text>
          </div>
        </template>

        <!-- YES_NO -->
        <template v-else-if="question.questionType === 'YES_NO'">
          <div class="orangehrm-survey-bar-row">
            <oxd-text tag="p" class="orangehrm-survey-bar-label">
              {{ $t('general.yes') }}
            </oxd-text>
            <div class="orangehrm-survey-bar-track">
              <div
                class="orangehrm-survey-bar-fill orangehrm-survey-bar-fill--yes"
                :style="{width: getOptionPercent(question.yesCount, responseCount) + '%'}"
              ></div>
            </div>
            <oxd-text tag="span" class="orangehrm-survey-bar-count">
              {{ question.yesCount }} ({{ getOptionPercent(question.yesCount, responseCount) }}%)
            </oxd-text>
          </div>
          <div class="orangehrm-survey-bar-row">
            <oxd-text tag="p" class="orangehrm-survey-bar-label">
              {{ $t('general.no') }}
            </oxd-text>
            <div class="orangehrm-survey-bar-track">
              <div
                class="orangehrm-survey-bar-fill orangehrm-survey-bar-fill--no"
                :style="{width: getOptionPercent(question.noCount, responseCount) + '%'}"
              ></div>
            </div>
            <oxd-text tag="span" class="orangehrm-survey-bar-count">
              {{ question.noCount }} ({{ getOptionPercent(question.noCount, responseCount) }}%)
            </oxd-text>
          </div>
        </template>
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
</template>

<script>
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';

const QUESTION_TYPE_LABELS = {
  TEXT: 'Text',
  MULTIPLE_CHOICE: 'Multiple Choice',
  SCALE_5: 'Scale (1-5)',
  SCALE_10: 'Scale (1-10)',
  YES_NO: 'Yes / No',
};

export default {
  props: {
    surveyId: {
      type: Number,
      required: true,
    },
  },

  setup(props) {
    const http = new APIService(
      window.appGlobal.baseUrl,
      `/api/v2/survey/surveys/${props.surveyId}/results`,
    );
    return {http};
  },

  data() {
    return {
      isLoading: false,
      surveyTitle: '',
      responseCount: 0,
      questionResults: [],
    };
  },

  beforeMount() {
    this.loadResults();
  },

  methods: {
    loadResults() {
      this.isLoading = true;
      this.http
        .getAll()
        .then((response) => {
          const data = response.data?.data;
          if (data) {
            this.surveyTitle = data.title || '';
            this.responseCount = data.responseCount || 0;
            this.questionResults = data.questions || [];
          }
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    getQuestionTypeLabel(type) {
      return QUESTION_TYPE_LABELS[type] || type;
    },
    getOptionPercent(count, total) {
      if (!total || total === 0) return 0;
      return Math.round((count / total) * 100);
    },
    onBack() {
      navigate('/survey/viewSurveys');
    },
  },
};
</script>

<style scoped>
.orangehrm-survey-subtitle {
  color: #666;
  margin-top: 0.25rem;
}
.orangehrm-survey-response-count {
  font-size: 0.9rem;
  color: #888;
}
.orangehrm-survey-result-section {
  margin-bottom: 1.5rem;
  padding: 1rem;
}
.orangehrm-survey-question-label {
  font-weight: 600;
  font-size: 1rem;
}
.orangehrm-survey-question-type-badge {
  font-size: 0.75rem;
  color: #888;
  background: #f0f0f0;
  padding: 0.1rem 0.5rem;
  border-radius: 8px;
  margin-left: 0.5rem;
}
.orangehrm-survey-text-answers {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.orangehrm-survey-text-answer {
  padding: 0.5rem;
  background: #f9f9f9;
  border-left: 3px solid #ff7b1c;
  border-radius: 0 4px 4px 0;
}
.orangehrm-survey-no-answers {
  color: #aaa;
  font-style: italic;
}
.orangehrm-survey-bar-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}
.orangehrm-survey-bar-label {
  min-width: 6rem;
  font-size: 0.9rem;
}
.orangehrm-survey-bar-track {
  flex: 1;
  height: 1.25rem;
  background: #f0f0f0;
  border-radius: 4px;
  overflow: hidden;
}
.orangehrm-survey-bar-fill {
  height: 100%;
  background: #ff7b1c;
  border-radius: 4px;
  transition: width 0.3s ease;
}
.orangehrm-survey-bar-fill--yes {
  background: #56ca00;
}
.orangehrm-survey-bar-fill--no {
  background: #e35252;
}
.orangehrm-survey-bar-count {
  min-width: 6rem;
  font-size: 0.85rem;
  color: #666;
}
.orangehrm-survey-avg {
  margin-bottom: 0.75rem;
}
.orangehrm-survey-loading,
.orangehrm-survey-empty {
  text-align: center;
  padding: 2rem;
  color: #888;
}
.orangehrm-form-actions {
  display: flex;
  justify-content: flex-end;
}
</style>
