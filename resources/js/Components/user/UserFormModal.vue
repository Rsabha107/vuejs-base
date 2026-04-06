<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

const statuses = ref([]);

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  mode: {
    type: String,
    default: "create",
  },
  user: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close", "saved"]);

const isEdit = computed(() => props.mode === "edit");

const form = useForm({
  id: null,
  name: "",
  email: "",
  password: "",
  status_id: "", // empty so placeholder is selected on create
});

onMounted(async () => {
  try {
    const res = await axios.get(route("global.statuses.get"));
    statuses.value = res.data;
    console.log("Loaded statuses:", statuses.value);
  } catch (e) {
    console.error("Failed to load statuses", e);
  }
});

watch(
  () => [props.show, props.user, props.mode],
  () => {
    form.clearErrors();
    form.reset();

    form.id = props.user?.id ?? null;
    form.name = props.user?.name ?? "";
    form.email = props.user?.email ?? "";
    form.password = "";

    // for edit: user.status must be the status ID
    // for create: keep empty so "-- Select Status --" is selected
    form.status_id = isEdit.value ? Number(props.user?.status_id ?? "") : "";
  },
  { immediate: true }
);

function closeModal() {
  emit("close");
}

function submit() {
  if (isEdit.value) {
    form.put(route("users.update", form.id), {
      preserveScroll: true,
      onSuccess: () => {
        if (window.toastr) window.toastr.success("User updated successfully");
        emit("saved");
      },
    });
  } else {
    form.post(route("users.store"), {
      preserveScroll: true,
      onSuccess: () => {
        if (window.toastr) window.toastr.success("User created successfully");
        emit("saved");
      },
    });
  }
}
</script>

<template>
  <div v-if="show" class="custom-modal-backdrop">
    <div class="custom-modal-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ isEdit ? "Edit User" : "Add User" }}</h5>
        <button type="button" class="btn-close" @click="closeModal"></button>
      </div>

      <form @submit.prevent="submit">
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
            <span class="text-muted">{{ isEdit ? "(leave blank to keep current)" : "" }}</span>
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

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            {{ form.processing ? "Saving..." : isEdit ? "Update" : "Save" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.custom-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 1rem;
}

.custom-modal-card {
  width: 100%;
  max-width: 560px;
  background: #fff;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}
</style>