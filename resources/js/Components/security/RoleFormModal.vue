<script setup>
import { ref, watch } from "vue";
import axios from "axios";

const props = defineProps({
  show: { type: Boolean, default: false },
  mode: { type: String, default: "create" }, // "create" | "edit"
  roleId: { type: Number, default: null },
  roleName: { type: String, default: "" },
});

const emit = defineEmits(["close", "saved"]);

const name = ref("");
const errors = ref({});
const isSaving = ref(false);

watch(
  () => [props.show, props.roleName],
  () => {
    name.value = props.roleName ?? "";
    errors.value = {};
  }
);

function closeModal() {
  emit("close");
}

async function save() {
  errors.value = {};
  if (!name.value.trim()) {
    errors.value = { name: "Role name is required." };
    return;
  }
  isSaving.value = true;
  try {
    if (props.mode === "create") {
      await axios.post("/roles", { name: name.value });
      window.toastr?.success("Role created successfully");
    } else {
      await axios.put(`/roles/${props.roleId}`, { name: name.value });
      window.toastr?.success("Role updated successfully");
    }
    emit("saved");
    closeModal();
  } catch (err) {
    const serverErrors = err.response?.data?.errors;
    if (serverErrors) errors.value = serverErrors;
    else window.toastr?.error("An error occurred. Please try again.");
  } finally {
    isSaving.value = false;
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="role-modal-overlay" @click.self="closeModal">
      <div class="role-modal-box">
        <!-- Header -->
        <div class="role-modal-header">
          <h5 class="role-modal-title">
            <i class="fa fa-users me-2 text-primary"></i>
            {{ mode === "create" ? "Add Role" : "Edit Role" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="role-modal-body">
          <label class="form-label fw-semibold" for="role-name-input">
            Role Name
          </label>
          <input
            id="role-name-input"
            v-model="name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': errors.name }"
            placeholder="Enter role name"
            @keydown.enter="save"
          />
          <div v-if="errors.name" class="invalid-feedback">
            {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
          </div>
        </div>

        <!-- Footer -->
        <div class="role-modal-footer">
          <button class="btn btn-light" @click="closeModal">Cancel</button>
          <button class="btn btn-primary" :disabled="isSaving" @click="save">
            <span
              v-if="isSaving"
              class="spinner-border spinner-border-sm me-1"
            ></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.role-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.role-modal-box {
  background: #fff;
  border-radius: 18px;
  width: 440px;
  max-width: 95vw;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
}

.role-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #eaecf6;
}

.role-modal-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1f2937;
}

.role-modal-body {
  padding: 1.25rem;
}

.role-modal-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid #eaecf6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>
