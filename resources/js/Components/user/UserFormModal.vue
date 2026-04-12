<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";

import UserFormFields from "@/Components/forms/UserFormFields.vue";

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
  status_id: "",
});

async function loadStatuses() {
  try {
    const res = await axios.get(route("global.statuses.get"));
    statuses.value = res.data ?? [];
  } catch (e) {
    console.error("Failed to load statuses", e);
  }
}

onMounted(() => {
  loadStatuses();
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
  <Teleport to="body">
    <div v-if="show" class="rp-modal-overlay" @click.self="closeModal">
      <div class="rp-modal-box">
        <!-- Header -->
        <div class="rp-modal-header">
          <h5 class="rp-modal-title">
            <i class="fa fa-user me-2 text-primary"></i>
            {{ isEdit ? "Edit User" : "Add User" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="rp-modal-body">
          <form id="user-form" @submit.prevent="submit">
            <UserFormFields
              :form="form"
              :statuses="statuses"
              :is-edit="isEdit"
            />
          </form>
        </div>

        <!-- Footer -->
        <div class="rp-modal-footer">
          <button type="button" class="btn btn-light" @click="closeModal">
            Cancel
          </button>
          <button
            type="submit"
            form="user-form"
            class="btn btn-primary"
            :disabled="form.processing"
          >
            <span
              v-if="form.processing"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            {{ form.processing ? "Saving..." : isEdit ? "Update" : "Save" }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   Modal
═══════════════════════════════════════════════════════════════ */
.rp-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rp-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 520px;
  max-width: 95vw;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.rp-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
  flex-shrink: 0;
}

.rp-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.rp-modal-body {
  padding: 1.25rem;
  overflow-y: auto;
  flex: 1 1 auto;
}

.rp-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}
</style>
