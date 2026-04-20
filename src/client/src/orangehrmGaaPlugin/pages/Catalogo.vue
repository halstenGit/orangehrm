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
  <div class="orangehrm-paper-container">
    <div class="orangehrm-header-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('gaa.catalogo') }}
      </oxd-text>
      <oxd-button
        :label="$t('gaa.adicionar_catalogo')"
        display-type="secondary"
        @click="abrirNovo"
      />
    </div>

    <div class="orangehrm-container">
      <table class="gaa-catalogo-table">
        <thead>
          <tr>
            <th>{{ $t('gaa.nome') }}</th>
            <th>{{ $t('gaa.tipo_item') }}</th>
            <th>{{ $t('gaa.quantidade_padrao') }}</th>
            <th>{{ $t('gaa.descricao') }}</th>
            <th>{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in catalogo" :key="c.id">
            <td>{{ c.nome }}</td>
            <td>{{ c.tipoItem }}</td>
            <td>{{ c.quantidadePadrao }}</td>
            <td>{{ c.descricao }}</td>
            <td>
              <oxd-icon-button name="pencil-fill" @click="editar(c)" />
              <oxd-icon-button name="trash" @click="excluir(c.id)" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <oxd-dialog v-if="editing" @update:show="editing = false">
      <template #header>
        <oxd-text type="card-title">
          {{ form.id ? $t('gaa.editar_item_catalogo') : $t('gaa.adicionar_catalogo') }}
        </oxd-text>
      </template>
      <oxd-form @submitValid="salvar">
        <oxd-input-field v-model="form.nome" :label="$t('gaa.nome')" :rules="rules.nome" />
        <oxd-input-field
          v-model="form.tipoItem"
          type="select"
          :label="$t('gaa.tipo_item')"
          :options="tipoItemOptions"
          :rules="rules.tipoItem"
        />
        <oxd-input-field
          v-model="form.quantidadePadrao"
          type="number"
          :label="$t('gaa.quantidade_padrao')"
        />
        <oxd-input-field
          v-model="form.descricao"
          type="textarea"
          :label="$t('gaa.descricao')"
        />
        <oxd-form-actions>
          <oxd-button :label="$t('general.cancel')" @click="editing = false" />
          <oxd-button
            :label="$t('general.save')"
            display-type="secondary"
            type="submit"
          />
        </oxd-form-actions>
      </oxd-form>
    </oxd-dialog>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {required} from '@ohrm/core/util/validation/rules';

export default {
  data() {
    return {
      catalogo: [],
      editing: false,
      form: {id: null, nome: '', tipoItem: null, quantidadePadrao: 1, descricao: ''},
      tipoItemOptions: [
        {id: 'ACESSO', label: this.$t('gaa.acesso')},
        {id: 'EQUIPAMENTO', label: this.$t('gaa.equipamento')},
      ],
      rules: {nome: [required], tipoItem: [required]},
    };
  },
  beforeMount() {
    this.fetch();
  },
  methods: {
    async fetch() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/catalogo');
      const res = await http.getAll({limit: 0});
      this.catalogo = res.data?.data || [];
    },
    abrirNovo() {
      this.form = {id: null, nome: '', tipoItem: null, quantidadePadrao: 1, descricao: ''};
      this.editing = true;
    },
    editar(c) {
      this.form = {
        id: c.id,
        nome: c.nome,
        tipoItem: this.tipoItemOptions.find((o) => o.id === c.tipoItem),
        quantidadePadrao: c.quantidadePadrao,
        descricao: c.descricao || '',
      };
      this.editing = true;
    },
    async salvar() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/catalogo');
      const payload = {
        nome: this.form.nome,
        tipoItem: this.form.tipoItem?.id || this.form.tipoItem,
        quantidadePadrao: parseInt(this.form.quantidadePadrao, 10) || 1,
        descricao: this.form.descricao,
      };
      if (this.form.id) {
        await http.update(this.form.id, payload);
      } else {
        await http.create(payload);
      }
      this.editing = false;
      this.fetch();
    },
    async excluir(id) {
      if (!window.confirm(this.$t('general.confirm_delete'))) return;
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/catalogo');
      await http.deleteAll({ids: [id]});
      this.fetch();
    },
  },
};
</script>

<style scoped>
.gaa-catalogo-table {
  width: 100%;
  border-collapse: collapse;
}
.gaa-catalogo-table th,
.gaa-catalogo-table td {
  text-align: left;
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
}
</style>
