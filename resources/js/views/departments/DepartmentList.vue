<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Departments</h5>
                <p class="text-muted mb-0">Manage hospital departments</p>
            </div>
            <button class="btn btn-primary" @click="showModal = true">
                <i class="bi bi-plus-lg me-1"></i> Add Department
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Department Name</th>
                            <th>Description</th>
                            <th>Doctors</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="dept in departments" :key="dept.department_id">
                            <td><span class="badge bg-light-primary text-primary">{{ dept.department_code }}</span></td>
                            <td class="fw-semibold">{{ dept.department_name }}</td>
                            <td>{{ dept.description || '-' }}</td>
                            <td>{{ dept.doctors_count || 0 }}</td>
                            <td>
                                <span class="badge" :class="dept.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ dept.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-light me-1" @click="editDepartment(dept)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" @click="deleteDepartment(dept)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="departments.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">No departments found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" :class="{ show: showModal }" :style="{ display: showModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingDept ? 'Edit Department' : 'Add Department' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveDepartment">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Department Code</label>
                                <input type="text" class="form-control" v-model="form.department_code" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department Name</label>
                                <input type="text" class="form-control" v-model="form.department_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" v-model="form.description" rows="3"></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showModal" @click="closeModal"></div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const departments = ref([]);
const showModal = ref(false);
const editingDept = ref(null);
const saving = ref(false);

const form = reactive({
    department_code: '',
    department_name: '',
    description: '',
    is_active: true
});

const fetchDepartments = async () => {
    const response = await axios.get('/api/departments');
    departments.value = response.data;
};

const editDepartment = (dept) => {
    editingDept.value = dept;
    Object.assign(form, {
        department_code: dept.department_code,
        department_name: dept.department_name,
        description: dept.description,
        is_active: dept.is_active
    });
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingDept.value = null;
    Object.assign(form, { department_code: '', department_name: '', description: '', is_active: true });
};

const saveDepartment = async () => {
    saving.value = true;
    try {
        if (editingDept.value) {
            await axios.put(`/api/departments/${editingDept.value.department_id}`, form);
        } else {
            await axios.post('/api/departments', form);
        }
        await fetchDepartments();
        closeModal();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving department');
    }
    saving.value = false;
};

const deleteDepartment = async (dept) => {
    if (confirm('Are you sure you want to delete this department?')) {
        await axios.delete(`/api/departments/${dept.department_id}`);
        await fetchDepartments();
    }
};

onMounted(fetchDepartments);
</script>
