<template>
    <div>
        <h4 class="mb-4">{{ isEdit ? 'Edit Patient' : 'New Patient' }}</h4>
        <div class="card">
            <div class="card-body">
                <form @submit.prevent="handleSubmit">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient Name *</label>
                            <input type="text" class="form-control" v-model="form.patient_name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" v-model="form.age">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" v-model="form.gender" required>
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" v-model="form.mobile">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" v-model="form.email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Blood Group</label>
                            <select class="form-select" v-model="form.blood_group">
                                <option value="">Select</option>
                                <option>A+</option><option>A-</option>
                                <option>B+</option><option>B-</option>
                                <option>AB+</option><option>AB-</option>
                                <option>O+</option><option>O-</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" v-model="form.address" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" v-model="form.city">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            {{ isEdit ? 'Update' : 'Save' }} Patient
                        </button>
                        <router-link to="/patients" class="btn btn-secondary ms-2">Cancel</router-link>
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
const saving = ref(false);
const isEdit = computed(() => !!route.params.id);

const form = reactive({
    patient_name: '',
    age: null,
    age_unit: 'years',
    gender: '',
    mobile: '',
    email: '',
    blood_group: '',
    address: '',
    city: ''
});

const fetchPatient = async () => {
    if (isEdit.value) {
        const response = await axios.get(`/api/patients/${route.params.id}`);
        Object.assign(form, response.data);
    }
};

const handleSubmit = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/patients/${route.params.id}`, form);
        } else {
            await axios.post('/api/patients', form);
        }
        router.push('/patients');
    } catch (error) {
        alert('Error saving patient');
    }
    saving.value = false;
};

onMounted(fetchPatient);
</script>
