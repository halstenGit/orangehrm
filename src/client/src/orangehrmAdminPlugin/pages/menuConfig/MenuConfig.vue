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
    <div class="orangehrm-paper-container">
      <div class="orangehrm-header-container">
        <oxd-text tag="h6" class="orangehrm-main-title">
          {{ $t('admin.menu_configuration') }}
        </oxd-text>
        <oxd-button
          :label="$t('general.save')"
          display-type="secondary"
          icon-name="save"
          :disabled="isLoading || !dirty"
          @click="onSave"
        />
      </div>
      <oxd-text tag="p" class="menuconfig-help">
        {{ $t('admin.menu_configuration_help') }}
      </oxd-text>

      <div v-if="isLoading" class="menuconfig-loading">
        <oxd-text tag="p">{{ $t('general.loading') }}</oxd-text>
      </div>

      <div
        v-for="(group, parentTitle) in grouped"
        v-else
        :key="parentTitle"
        class="menuconfig-group"
      >
        <oxd-text tag="h6" class="menuconfig-group-title">
          {{ parentTitle === '__top__' ? $t('admin.top_level_menus') : parentTitle }}
        </oxd-text>
        <table class="menuconfig-table">
          <thead>
            <tr>
              <th>{{ $t('general.title') }}</th>
              <th class="menuconfig-col-order">{{ $t('admin.order') }}</th>
              <th class="menuconfig-col-icon">{{ $t('admin.icon_name') }}</th>
              <th class="menuconfig-col-status">{{ $t('admin.visible') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in group" :key="row.id" :class="{'menuconfig-changed': isRowChanged(row)}">
              <td>{{ row.menuTitle }}</td>
              <td>
                <input
                  v-model.number="row.orderHint"
                  type="number"
                  min="0"
                  step="10"
                  class="menuconfig-input-number"
                  @input="markDirty"
                />
              </td>
              <td>
                <input
                  v-model="row.icon"
                  type="text"
                  :placeholder="$t('admin.icon_placeholder')"
                  class="menuconfig-input-text"
                  @input="markDirty"
                />
              </td>
              <td>
                <input
                  v-model="row.status"
                  type="checkbox"
                  @change="markDirty"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';

export default {
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/menu-items',
    );
    return {http};
  },

  data() {
    return {
      isLoading: false,
      items: [],
      original: {},
      dirty: false,
    };
  },

  computed: {
    grouped() {
      const out = {};
      for (const item of this.items) {
        const key = item.level === 1 ? '__top__' : (item.parentTitle || '?');
        if (!out[key]) out[key] = [];
        out[key].push(item);
      }
      // Top-level primeiro (chave '__top__'), depois grupos por nome do pai.
      return Object.keys(out)
        .sort((a, b) => (a === '__top__' ? -1 : b === '__top__' ? 1 : a.localeCompare(b)))
        .reduce((acc, k) => ((acc[k] = out[k]), acc), {});
    },
  },

  beforeMount() {
    this.load();
  },

  methods: {
    load() {
      this.isLoading = true;
      this.http
        .getAll()
        .then((res) => {
          const data = res.data?.data || [];
          this.items = data.map((it) => ({
            id: it.id,
            menuTitle: it.menuTitle,
            level: it.level,
            parentId: it.parentId,
            parentTitle: it.parentTitle,
            orderHint: it.orderHint,
            status: !!it.status,
            icon: it.icon || '',
          }));
          // Snapshot p/ detectar quais linhas mudaram no Save.
          this.original = {};
          for (const it of this.items) {
            this.original[it.id] = {
              orderHint: it.orderHint,
              status: it.status,
              icon: it.icon,
            };
          }
          this.dirty = false;
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    markDirty() {
      this.dirty = true;
    },
    isRowChanged(row) {
      const orig = this.original[row.id];
      if (!orig) return false;
      return (
        orig.orderHint !== row.orderHint ||
        orig.status !== row.status ||
        orig.icon !== row.icon
      );
    },
    onSave() {
      const changed = this.items
        .filter((row) => this.isRowChanged(row))
        .map((row) => ({
          id: row.id,
          orderHint: row.orderHint,
          status: row.status,
          icon: row.icon,
        }));
      if (changed.length === 0) {
        this.dirty = false;
        return;
      }
      this.isLoading = true;
      this.http
        .request({
          method: 'PUT',
          url: `${window.appGlobal.baseUrl}/api/v2/admin/menu-items`,
          data: {items: changed},
        })
        .then(() => this.$toast.saveSuccess())
        .then(() => this.load())
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>

<style scoped>
.menuconfig-help {
  color: #666;
  margin-bottom: 1rem;
  font-size: 0.85rem;
}
.menuconfig-loading {
  text-align: center;
  padding: 2rem;
  color: #888;
}
.menuconfig-group {
  margin-bottom: 1.5rem;
}
.menuconfig-group-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #ff7b1c;
}
.menuconfig-table {
  width: 100%;
  border-collapse: collapse;
}
.menuconfig-table th,
.menuconfig-table td {
  text-align: left;
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
  font-size: 0.9rem;
}
.menuconfig-table th {
  background: #f9f9f9;
  font-weight: 600;
}
.menuconfig-changed {
  background: #fff8e1;
}
.menuconfig-col-order {
  width: 100px;
}
.menuconfig-col-icon {
  width: 200px;
}
.menuconfig-col-status {
  width: 80px;
  text-align: center;
}
.menuconfig-input-number {
  width: 80px;
  padding: 0.25rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}
.menuconfig-input-text {
  width: 100%;
  padding: 0.25rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-family: monospace;
}
</style>
