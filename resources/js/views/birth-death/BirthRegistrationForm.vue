<template>
    <div>
        <h4 class="mb-4">
            <i class="bi bi-balloon-heart me-2"></i>
            {{ isEdit ? 'Edit Birth Registration' : 'New Birth Registration' }}
        </h4>
        <form @submit.prevent="handleSubmit">
            <!-- Mother & Child Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Mother & Child Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Mother (Patient) *</label>
                            <select class="form-select" v-model="form.mother_patient_id" required>
                                <option value="">Select Patient</option>
                                <option v-for="p in patients" :key="p.patient_id" :value="p.patient_id">
                                    {{ p.patient_name }} ({{ p.uhid }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mother's Age at Delivery</label>
                            <input type="number" class="form-control" v-model="form.mother_age" min="15" max="60">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">IPD Admission</label>
                            <select class="form-select" v-model="form.ipd_id">
                                <option value="">Not Linked</option>
                                <option v-for="ipd in ipdAdmissions" :key="ipd.ipd_id" :value="ipd.ipd_id">
                                    {{ ipd.ipd_number }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Name *</label>
                            <input type="text" class="form-control" v-model="form.mother_name" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Aadhar</label>
                            <input type="text" class="form-control" v-model="form.mother_aadhar" maxlength="12" placeholder="12 digits">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Name *</label>
                            <input type="text" class="form-control" v-model="form.father_name" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Aadhar</label>
                            <input type="text" class="form-control" v-model="form.father_aadhar" maxlength="12" placeholder="12 digits">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Child's Name</label>
                            <input type="text" class="form-control" v-model="form.child_name" maxlength="100" placeholder="Can be added later">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Birth Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Birth Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth *</label>
                            <input type="date" class="form-control" v-model="form.date_of_birth" required :max="today">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Time of Birth *</label>
                            <input type="time" class="form-control" v-model="form.time_of_birth" required>
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
                        <div class="col-md-3">
                            <label class="form-label">Birth Weight (kg)</label>
                            <input type="number" class="form-control" v-model="form.weight_kg" step="0.01" min="0.5" max="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Birth Type *</label>
                            <select class="form-select" v-model="form.birth_type" required>
                                <option value="">Select</option>
                                <option value="live">Live Birth</option>
                                <option value="stillbirth">Stillbirth</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Delivery Type *</label>
                            <select class="form-select" v-model="form.delivery_type" required>
                                <option value="">Select</option>
                                <option value="normal">Normal/Vaginal</option>
                                <option value="cesarean">Cesarean Section</option>
                                <option value="assisted">Assisted (Forceps/Vacuum)</option>
                                <option value="water">Water Birth</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Attending Doctor *</label>
                            <select class="form-select" v-model="form.attending_doctor_id" required>
                                <option value="">Select Doctor</option>
                                <option v-for="d in doctors" :key="d.doctor_id" :value="d.doctor_id">
                                    Dr. {{ d.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Place of Birth</label>
                            <input type="text" class="form-control" v-model="form.place_of_birth" maxlength="100" placeholder="e.g., Labor Room, OT">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informant Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Informant Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Informant Name *</label>
                            <input type="text" class="form-control" v-model="form.informant_name" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relation to Child *</label>
                            <select class="form-select" v-model="form.informant_relation" required>
                                <option value="">Select</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Grandfather">Grandfather</option>
                                <option value="Grandmother">Grandmother</option>
                                <option value="Other Relative">Other Relative</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Informant Address</label>
                            <input type="text" class="form-control" v-model="form.informant_address">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permanent Address -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Permanent Address</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" v-model="form.permanent_address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ isEdit ? 'Update' : 'Register' }} Birth
                </button>
                <router-link to="/birth-registrations" class="btn btn-secondary ms-2">Cancel</router-link>
            </div>
        </form>
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
const today = new Date().toISOString().split('T')[0];

const patients = ref([]);
const doctors = ref([]);
const ipdAdmissions = ref([]);

const form = reactive({
    mother_patient_id: '',
    ipd_id: '',
    mother_name: '',
    mother_aadhar: '',
    mother_age: null,
    father_name: '',
    father_aadhar: '',
    child_name: '',
    date_of_birth: today,
    time_of_birth: '',
    gender: '',
    weight_kg: null,
    birth_type: '',
    delivery_type: '',
    attending_doctor_id: '',
    place_of_birth: '',
    informant_name: '',
    informant_relation: '',
    informant_address: '',
    permanent_address: ''
});

const loadData = async () => {
    try {
        const [patientsRes, doctorsRes, ipdRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/doctors'),
            axios.get('/api/ipd-admissions?status=admitted&per_page=500')
        ]);
        patients.value = patientsRes.data.data || patientsRes.data;
        doctors.value = doctorsRes.data.data || doctorsRes.data;
        ipdAdmissions.value = ipdRes.data.data || ipdRes.data;
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const fetchRegistration = async () => {
    if (isEdit.value) {
        try {
            const response = await axios.get(`/api/birth-registrations/${route.params.id}`);
            const reg = response.data.registration || response.data;
            Object.assign(form, {
                mother_patient_id: reg.mother_patient_id,
                ipd_id: reg.ipd_id || '',
                mother_name: reg.mother_name,
                mother_aadhar: reg.mother_aadhar || '',
                mother_age: reg.mother_age_at_delivery,
                father_name: reg.father_name,
                father_aadhar: reg.father_aadhar || '',
                child_name: reg.child_name || '',
                date_of_birth: reg.date_of_birth?.split('T')[0],
                time_of_birth: reg.time_of_birth,
                gender: reg.gender,
                weight_kg: reg.weight_kg,
                birth_type: reg.birth_type,
                delivery_type: reg.delivery_type,
                attending_doctor_id: reg.attending_doctor_id,
                place_of_birth: reg.place_of_birth || '',
                informant_name: reg.informant_name,
                informant_relation: reg.informant_relation,
                informant_address: reg.informant_address || '',
                permanent_address: reg.permanent_address || ''
            });
        } catch (error) {
            console.error('Failed to load registration:', error);
        }
    }
};

const handleSubmit = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/birth-registrations/${route.params.id}`, form);
        } else {
            await axios.post('/api/birth-registrations', form);
        }
        router.push('/birth-registrations');
    } catch (error) {
        const msg = error.response?.data?.message || 'Error saving registration';
        alert(msg);
        console.error('Save error:', error);
    }
    saving.value = false;
};

onMounted(async () => {
    await loadData();
    await fetchRegistration();
});
</script>
