<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">{{ isEdit ? 'Edit Doctor' : 'Add Doctor' }}</h5>
                <p class="text-muted mb-0">{{ isEdit ? 'Update doctor information' : 'Register a new doctor' }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="saveDoctor">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Doctor Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.doctor_code" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.full_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Qualification</label>
                            <input type="text" class="form-control" v-model="form.qualification" placeholder="e.g., MBBS, MD">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Specialization <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" v-model="form.specialization" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.department_id" required>
                                <option value="">Select Department</option>
                                <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                    {{ dept.department_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Consultation Fee</label>
                            <input type="number" class="form-control" v-model="form.consultation_fee" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" v-model="form.mobile">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="form.email">
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" v-model="form.opd_available" id="opdAvailable">
                                <label class="form-check-label" for="opdAvailable">OPD Available</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" v-model="form.ipd_available" id="ipdAvailable">
                                <label class="form-check-label" for="ipdAvailable">IPD Available</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Update' : 'Save' }} Doctor
                        </button>
                        <router-link to="/doctors" class="btn btn-light">Cancel</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

const departments = ref([]);
const saving = ref(false);

const form = reactive({
    doctor_code: '',
    full_name: '',
    qualification: '',
    specialization: '',
    department_id: '',
    consultation_fee: '',
    mobile: '',
    email: '',
    opd_available: true,
    ipd_available: true,
    is_active: true
});

const fetchData = async () => {
    const deptRes = await axios.get('/api/departments');
    departments.value = deptRes.data;

    if (route.params.id) {
        const response = await axios.get(`/api/doctors/${route.params.id}`);
        Object.assign(form, response.data);
    }
};

const saveDoctor = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/doctors/${route.params.id}`, form);
        } else {
            await axios.post('/api/doctors', form);
        }
        router.push('/doctors');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving doctor');
    }
    saving.value = false;
};

onMounted(fetchData);
</script>
