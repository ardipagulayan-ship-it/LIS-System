<template>
  <div class="signature-editor">
    <div class="form-row">
      <label>Mode</label>
      <select v-model="local.mode" @change="emitUpdate">
        <option value="specific_user">Specific User(s)</option>
        <option value="current_logged_user">Current Logged User</option>
        <option value="role_pathologist">Pathologist Role</option>
      </select>
    </div>

    <div class="form-row">
      <label>Layout</label>
      <select v-model="local.layout" @change="emitUpdate">
        <option value="1col">1 Column</option>
        <option value="2col">2 Columns</option>
        <option value="3col">3 Columns</option>
      </select>
    </div>

    <div v-if="local.mode === 'specific_user'" class="form-section">
      <label>Signatories</label>
      <div class="signatories-list">
        <div v-for="(sig, idx) in local.signatories" :key="idx" class="signatory-row">
          <input type="text" v-model="sig.name" @input="emitUpdate" placeholder="Name" />
          <input type="text" v-model="sig.role" @input="emitUpdate" placeholder="Role" />
          <button @click="removeSignatory(idx)" type="button" class="btn-remove">×</button>
        </div>
      </div>
      <button @click="addSignatory" type="button" class="btn-add">+ Add Signatory</button>
    </div>

    <div class="form-row">
      <label>Width</label>
      <input type="text" v-model="local.width" @input="emitUpdate" placeholder="e.g. 100% or 400px" />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
const props = defineProps({ element: { type: Object, required: true } });
const emit = defineEmits(['update']);

const local = reactive({
  mode: props.element.mode || 'specific_user',
  layout: props.element.layout || '1col',
  signatories: JSON.parse(JSON.stringify(props.element.signatories || [{ name: 'Signatory', role: 'Role' }])),
  width: props.element.width || '100%',
});

watch(() => props.element, (v) => {
  local.mode = v.mode || 'specific_user';
  local.layout = v.layout || '1col';
  // Only reset signatories if they exist in the element
  if (v.signatories && v.signatories.length > 0) {
    local.signatories = JSON.parse(JSON.stringify(v.signatories));
  }
  // If no signatories provided, keep existing ones (don't reset to empty)
  local.width = v.width || '100%';
});

const emitUpdate = () => {
  const payload = {
    mode: local.mode,
    layout: local.layout,
    width: local.width,
  };
  if (local.mode === 'specific_user') {
    payload.signatories = local.signatories;
  }
  emit('update', payload);
};

const addSignatory = () => {
  local.signatories.push({ name: 'New Signatory', role: 'Role' });
  emitUpdate();
};

const removeSignatory = (idx) => {
  local.signatories.splice(idx, 1);
  emitUpdate();
};
</script>

<style scoped>
.signature-editor { display: flex; flex-direction: column; gap: 12px; }
.form-row { display: flex; flex-direction: column; }
.form-section { display: flex; flex-direction: column; gap: 8px; }
label { font-size: 12px; color: #606266; margin-bottom: 4px }
input, select { padding: 6px 8px; border: 1px solid #dcdfe6; border-radius: 4px; font-size: 12px; }
.signatories-list { display: flex; flex-direction: column; gap: 6px; }
.signatory-row { display: flex; gap: 4px; align-items: center; }
.signatory-row input { flex: 1; }
.btn-remove { padding: 4px 8px; background: var(--color-danger); color: white; border: none; border-radius: 3px; cursor: pointer; }
.btn-remove:hover { background: var(--color-danger-dark); }
.btn-add { padding: 6px 12px; background: var(--color-primary); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
.btn-add:hover { background: var(--color-primary-dark); }
</style>
