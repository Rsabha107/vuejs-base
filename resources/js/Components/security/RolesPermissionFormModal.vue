<script setup>
import { ref, computed, watch } from "vue";
import axios from "axios";

const props = defineProps({
  show: { type: Boolean, default: false },
  mode: { type: String, default: "create" }, // "create" | "edit"
  roleId: { type: Number, default: null },
  roleName: { type: String, default: "" },
});

const emit = defineEmits(["close", "saved"]);

const allRoles = ref([]);
const allPermissions = ref([]);
const permSearch = ref("");
const formErrors = ref({});
const isSaving = ref(false);
const isLoading = ref(false);

const roleId = ref(null);
const permissionIds = ref([]);

const filteredPermissions = computed(() =>
  permSearch.value.trim()
    ? allPermissions.value.filter((p) =>
        p.name.toLowerCase().includes(permSearch.value.toLowerCase())
      )
    : allPermissions.value
);

async function loadDropdownData() {
  if (allPermissions.value.length && allRoles.value.length) return;
  const [permsRes, rolesRes] = await Promise.all([
    axios.get("/api/roles-permissions/all-permissions"),
    axios.get("/api/roles-permissions/all-roles"),
  ]);
  allPermissions.value = permsRes.data;
  allRoles.value = rolesRes.data;
}

watch(
  () => props.show,
  async (val) => {
    if (!val) return;
    isLoading.value = true;
    permSearch.value = "";
    formErrors.value = {};
    roleId.value = null;
    permissionIds.value = [];

    await loadDropdownData();

    if (props.mode === "edit" && props.roleId) {
      const { data } = await axios.get(
        `/api/roles-permissions?limit=1&offset=0&search=${encodeURIComponent(props.roleName)}`
      );
      const row = data.rows?.find((r) => r.id === props.roleId);
      permissionIds.value = row ? row.permissions.map((p) => p.id) : [];
    }

    isLoading.value = false;
  }
);

function closeModal() {
  emit("close");
}

async function save() {
  formErrors.value = {};

  const id = props.mode === "create" ? roleId.value : props.roleId;

  if (props.mode === "create" && !id) {
    formErrors.value.role_id = "Please select a role.";
    return;
  }

  isSaving.value = true;
  try {
    await axios.put(`/api/roles-permissions/${id}`, {
      permission_ids: permissionIds.value,
    });
    window.toastr?.success("Permissions saved successfully");
    emit("saved");
    closeModal();
  } catch (err) {
    const errors = err.response?.data?.errors;
    if (errors) formErrors.value = errors;
    else window.toastr?.error("An error occurred. Please try again.");
  } finally {
    isSaving.value = false;
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
            <i class="fa fa-key me-2 text-primary"></i>
            {{ mode === "create" ? "Assign Permissions to Role" : "Edit Role Permissions" }}
          </h5>
          <button class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Body -->
        <div class="rp-modal-body">
          <div v-if="isLoading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
          </div>

          <template v-else>
            <!-- Role selector (create mode) -->
            <div v-if="mode === 'create'" class="mb-4">
              <label class="form-label fw-semibold">Role</label>
              <select
                v-model="roleId"
                class="form-select"
                :class="{ 'is-invalid': formErrors.role_id }"
              >
                <option value="">Select a role…</option>
                <option v-for="role in allRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
              <div v-if="formErrors.role_id" class="invalid-feedback">
                {{ formErrors.role_id }}
              </div>
            </div>

            <!-- Role label (edit mode) -->
            <div v-else class="mb-4">
              <label class="form-label fw-semibold">Role: </label>
              <div class="role-label ms-2">{{ roleName }}</div>
            </div>

            <!-- Permissions -->
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label fw-semibold mb-0">
                  Permissions
                  <span class="badge-count">{{ permissionIds.length }} selected</span>
                </label>
                <button
                  type="button"
                  class="btn btn-link btn-sm p-0 text-muted text-decoration-none"
                  @click="permissionIds = []"
                >
                  Clear all
                </button>
              </div>

              <input
                v-model="permSearch"
                type="text"
                class="form-control form-control-sm mb-2"
                placeholder="Search permissions…"
              />

              <div class="perm-list">
                <label
                  v-for="perm in filteredPermissions"
                  :key="perm.id"
                  class="perm-check-item"
                  :class="{ 'is-checked': permissionIds.includes(perm.id) }"
                >
                  <input
                    type="checkbox"
                    :value="perm.id"
                    v-model="permissionIds"
                    class="perm-checkbox"
                  />
                  <span>{{ perm.name }}</span>
                </label>

                <div
                  v-if="filteredPermissions.length === 0"
                  class="text-muted small py-3 text-center"
                >
                  No permissions found.
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Footer -->
        <div class="rp-modal-footer">
          <button class="btn btn-light" @click="closeModal">Cancel</button>
          <button class="btn btn-primary" :disabled="isSaving || isLoading" @click="save">
            <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
            Save
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
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

.role-label {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
  background: #f5f7ff;
  border: 1px solid #c7d2fe;
  border-radius: 10px;
  padding: 8px 14px;
  display: inline-block;
}

.badge-count {
  display: inline-block;
  background: #eef2ff;
  color: #3a5bd9;
  font-size: 11px;
  font-weight: 700;
  border-radius: 50px;
  padding: 1px 8px;
  margin-left: 6px;
}

.perm-list {
  max-height: 280px;
  overflow-y: auto;
  border: 1px solid #eaecf6;
  border-radius: 12px;
  padding: 6px 4px;
}

.perm-check-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.12s;
  font-size: 13px;
  color: #374151;
  user-select: none;
}

.perm-check-item:hover {
  background: #f5f7ff;
}

.perm-check-item.is-checked {
  background: #eef2ff;
  color: #3a5bd9;
  font-weight: 500;
}

.perm-checkbox {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border-color: #5b8df6;
  flex-shrink: 0;
  cursor: pointer;
}

.perm-checkbox:checked {
  background-color: #3a5bd9;
  border-color: #3a5bd9;
}
</style>
