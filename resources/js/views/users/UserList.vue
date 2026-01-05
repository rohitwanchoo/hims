<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Users</h5>
                <p class="text-muted mb-0">Manage system users and access</p>
            </div>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-1"></i> Add User
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id">
                            <td class="fw-semibold">{{ user.username }}</td>
                            <td>{{ user.full_name }}</td>
                            <td>{{ user.email }}</td>
                            <td><span class="badge bg-primary">{{ user.role }}</span></td>
                            <td>{{ user.department?.department_name || '-' }}</td>
                            <td>
                                <span class="badge" :class="user.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-light me-1" @click="openModal(user)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-light text-danger" @click="deleteUser(user)" v-if="user.role !== 'admin'">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
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
                        <h5 class="modal-title">{{ editingUser ? 'Edit User' : 'Add User' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveUser">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" v-model="form.full_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" v-model="form.email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password {{ editingUser ? '(leave blank to keep current)' : '' }} <span class="text-danger" v-if="!editingUser">*</span></label>
                                <input type="password" class="form-control" v-model="form.password" :required="!editingUser">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.role" required>
                                    <option value="admin">Admin</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="nurse">Nurse</option>
                                    <option value="receptionist">Receptionist</option>
                                    <option value="lab_technician">Lab Technician</option>
                                    <option value="pharmacist">Pharmacist</option>
                                    <option value="accountant">Accountant</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" v-model="form.department_id">
                                    <option value="">No Department</option>
                                    <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                        {{ dept.department_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="userActive">
                                <label class="form-check-label" for="userActive">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save User
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

const users = ref([]);
const departments = ref([]);
const showModal = ref(false);
const editingUser = ref(null);
const saving = ref(false);

const form = reactive({
    username: '',
    full_name: '',
    email: '',
    password: '',
    role: 'receptionist',
    department_id: '',
    is_active: true
});

const fetchUsers = async () => {
    const response = await axios.get('/api/users');
    users.value = response.data;
};

const fetchDepartments = async () => {
    const response = await axios.get('/api/departments');
    departments.value = response.data;
};

const openModal = (user = null) => {
    editingUser.value = user;
    if (user) {
        Object.assign(form, {
            username: user.username,
            full_name: user.full_name,
            email: user.email,
            password: '',
            role: user.role,
            department_id: user.department_id || '',
            is_active: user.is_active
        });
    } else {
        Object.assign(form, {
            username: '', full_name: '', email: '', password: '',
            role: 'receptionist', department_id: '', is_active: true
        });
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingUser.value = null;
};

const saveUser = async () => {
    saving.value = true;
    try {
        const data = { ...form };
        if (editingUser.value && !data.password) delete data.password;

        if (editingUser.value) {
            await axios.put(`/api/users/${editingUser.value.id}`, data);
        } else {
            await axios.post('/api/users', data);
        }
        await fetchUsers();
        closeModal();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving user');
    }
    saving.value = false;
};

const deleteUser = async (user) => {
    if (confirm(`Delete user ${user.username}?`)) {
        await axios.delete(`/api/users/${user.id}`);
        await fetchUsers();
    }
};

onMounted(() => {
    fetchUsers();
    fetchDepartments();
});
</script>
