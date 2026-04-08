<script setup>
const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  statuses: {
    type: Array,
    default: () => [],
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input v-model="form.name" type="text" class="form-control" />
      <div v-if="form.errors.name" class="text-danger small mt-1">
        {{ form.errors.name }}
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input v-model="form.email" type="email" class="form-control" />
      <div v-if="form.errors.email" class="text-danger small mt-1">
        {{ form.errors.email }}
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">
        Password
        <span class="text-muted">
          {{ isEdit ? "(leave blank to keep current)" : "" }}
        </span>
      </label>
      <input v-model="form.password" type="password" class="form-control" />
      <div v-if="form.errors.password" class="text-danger small mt-1">
        {{ form.errors.password }}
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Status</label>
      <select v-model="form.status_id" class="form-select">
        <option value="">-- Select Status --</option>
        <option
          v-for="status in statuses"
          :key="status.id"
          :value="status.id"
        >
          {{ status.name }}
        </option>
      </select>
      <div v-if="form.errors.status_id" class="text-danger small mt-1">
        {{ form.errors.status_id }}
      </div>
    </div>
</template>