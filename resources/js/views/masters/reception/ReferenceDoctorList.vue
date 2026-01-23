<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Reference Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Reference
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Qualification</th>
                                <th>Specialization</th>
                                <th>Mobile</th>
                                <th>Reg No</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>Loading...
                                </td>
                            </tr>
                            <tr v-else-if="items.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">No records found</td>
                            </tr>
                            <tr v-for="(item, index) in items" :key="item.reference_doctor_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.doctor_name }}</td>
                                <td>{{ item.qualification_master?.qualification_name || item.qualification || '-' }}</td>
                                <td>{{ item.department?.department_name || item.specialization || '-' }}</td>
                                <td>{{ item.mobile || '-' }}</td>
                                <td>{{ item.registration_no || '-' }}</td>
                                <td>
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        v-if="!item.usage_count"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="deleteItem(item)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <span v-else class="badge bg-info" title="In use by patients">
                                        <i class="bi bi-link-45deg"></i> In Use
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="itemModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Reference' : 'Add Reference' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <!-- Basic Info -->
                            <h6 class="mb-3 text-primary">Basic Information</h6>
                            <div class="row">
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Prefix</label>
                                    <select class="form-select" v-model="form.prefix_id">
                                        <option value="">Select</option>
                                        <option v-for="p in prefixes" :key="p.prefix_id" :value="p.prefix_id">{{ p.prefix_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.first_name" required maxlength="50">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" v-model="form.middle_name" maxlength="50">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" class="form-control" v-model="form.last_name" maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" v-model="form.gender_id">
                                        <option value="">Select Gender</option>
                                        <option v-for="g in genders" :key="g.gender_id" :value="g.gender_id">{{ g.gender_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Blood Group</label>
                                    <select class="form-select" v-model="form.blood_group_id">
                                        <option value="">Select Blood Group</option>
                                        <option v-for="bg in bloodGroups" :key="bg.blood_group_id" :value="bg.blood_group_id">{{ bg.blood_group_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" v-model="form.dob">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Qualification</label>
                                    <select class="form-select" v-model="form.qualification_id">
                                        <option value="">Select Qualification</option>
                                        <option v-for="q in qualifications" :key="q.qualification_id" :value="q.qualification_id">{{ q.qualification_name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Skill Set / Department</label>
                                    <select class="form-select" v-model="form.department_id">
                                        <option value="">Select Department</option>
                                        <option v-for="d in departments" :key="d.department_id" :value="d.department_id">{{ d.department_name }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Registration No</label>
                                    <input type="text" class="form-control" v-model="form.registration_no" maxlength="50">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Practice No</label>
                                    <input type="text" class="form-control" v-model="form.practice_no" maxlength="50">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Clinic Name</label>
                                    <input type="text" class="form-control" v-model="form.clinic_name" maxlength="150">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" class="form-control" v-model="form.mobile" maxlength="15">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="form.email" maxlength="100">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="is_active" v-model="form.is_active">
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Tabs -->
                            <ul class="nav nav-tabs mt-4" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#residence" role="tab">Residence Address</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#clinic" role="tab">Clinic Address</a>
                                </li>
                            </ul>
                            <div class="tab-content border border-top-0 p-3">
                                <!-- Residence Address Tab -->
                                <div class="tab-pane fade show active" id="residence" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Address Line 1</label>
                                            <input type="text" class="form-control" v-model="form.res_address_line1" maxlength="200">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Address Line 2</label>
                                            <input type="text" class="form-control" v-model="form.res_address_line2" maxlength="200">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Country</label>
                                            <select class="form-select" v-model="form.res_country_id" @change="onResCountryChange">
                                                <option value="">Select Country</option>
                                                <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">State</label>
                                            <select class="form-select" v-model="form.res_state_id" @change="onResStateChange">
                                                <option value="">Select State</option>
                                                <option v-for="s in resStates" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">District</label>
                                            <select class="form-select" v-model="form.res_district_id" @change="onResDistrictChange">
                                                <option value="">Select District</option>
                                                <option v-for="d in resDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">City/Taluka</label>
                                            <select class="form-select" v-model="form.res_city_id">
                                                <option value="">Select City</option>
                                                <option v-for="c in resCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Pin Code</label>
                                            <input type="text" class="form-control" v-model="form.res_pincode" maxlength="10">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Fax</label>
                                            <input type="text" class="form-control" v-model="form.res_fax" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Tel (1)</label>
                                            <input type="text" class="form-control" v-model="form.res_tel1" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Tel (2)</label>
                                            <input type="text" class="form-control" v-model="form.res_tel2" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Mobile</label>
                                            <input type="text" class="form-control" v-model="form.res_mobile" maxlength="15">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" v-model="form.res_email" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Website</label>
                                            <input type="text" class="form-control" v-model="form.res_website" maxlength="200">
                                        </div>
                                    </div>
                                </div>

                                <!-- Clinic Address Tab -->
                                <div class="tab-pane fade" id="clinic" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Address Line 1</label>
                                            <input type="text" class="form-control" v-model="form.clinic_address_line1" maxlength="200">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Address Line 2</label>
                                            <input type="text" class="form-control" v-model="form.clinic_address_line2" maxlength="200">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Country</label>
                                            <select class="form-select" v-model="form.clinic_country_id" @change="onClinicCountryChange">
                                                <option value="">Select Country</option>
                                                <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">State</label>
                                            <select class="form-select" v-model="form.clinic_state_id" @change="onClinicStateChange">
                                                <option value="">Select State</option>
                                                <option v-for="s in clinicStates" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">District</label>
                                            <select class="form-select" v-model="form.clinic_district_id" @change="onClinicDistrictChange">
                                                <option value="">Select District</option>
                                                <option v-for="d in clinicDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">City/Taluka</label>
                                            <select class="form-select" v-model="form.clinic_city_id">
                                                <option value="">Select City</option>
                                                <option v-for="c in clinicCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Pin Code</label>
                                            <input type="text" class="form-control" v-model="form.clinic_pincode" maxlength="10">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Fax</label>
                                            <input type="text" class="form-control" v-model="form.clinic_fax" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Tel (1)</label>
                                            <input type="text" class="form-control" v-model="form.clinic_tel1" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Tel (2)</label>
                                            <input type="text" class="form-control" v-model="form.clinic_tel2" maxlength="20">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Mobile</label>
                                            <input type="text" class="form-control" v-model="form.clinic_mobile" maxlength="15">
                                        </div>
                                        <div class="col-md-2 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" v-model="form.clinic_email" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Website</label>
                                            <input type="text" class="form-control" v-model="form.clinic_website" maxlength="200">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editingItem ? 'Update' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const items = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

// Master data
const prefixes = ref([]);
const genders = ref([]);
const bloodGroups = ref([]);
const qualifications = ref([]);
const departments = ref([]);
const countries = ref([]);
const allStates = ref([]);
const allDistricts = ref([]);
const allCities = ref([]);

// Computed filtered lists for residence address
const resStates = computed(() => {
    if (!form.res_country_id) return [];
    return allStates.value.filter(s => s.country_id == form.res_country_id);
});

const resDistricts = computed(() => {
    if (!form.res_state_id) return [];
    return allDistricts.value.filter(d => d.state_id == form.res_state_id);
});

const resCities = computed(() => {
    if (!form.res_district_id) return [];
    return allCities.value.filter(c => c.district_id == form.res_district_id);
});

// Computed filtered lists for clinic address
const clinicStates = computed(() => {
    if (!form.clinic_country_id) return [];
    return allStates.value.filter(s => s.country_id == form.clinic_country_id);
});

const clinicDistricts = computed(() => {
    if (!form.clinic_state_id) return [];
    return allDistricts.value.filter(d => d.state_id == form.clinic_state_id);
});

const clinicCities = computed(() => {
    if (!form.clinic_district_id) return [];
    return allCities.value.filter(c => c.district_id == form.clinic_district_id);
});

const form = reactive({
    prefix_id: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    gender_id: '',
    blood_group_id: '',
    qualification_id: '',
    department_id: '',
    registration_no: '',
    practice_no: '',
    dob: '',
    clinic_name: '',
    mobile: '',
    email: '',
    // Residence Address
    res_address_line1: '',
    res_address_line2: '',
    res_country_id: '',
    res_state_id: '',
    res_district_id: '',
    res_city_id: '',
    res_pincode: '',
    res_fax: '',
    res_tel1: '',
    res_tel2: '',
    res_mobile: '',
    res_email: '',
    res_website: '',
    // Clinic Address
    clinic_address_line1: '',
    clinic_address_line2: '',
    clinic_country_id: '',
    clinic_state_id: '',
    clinic_district_id: '',
    clinic_city_id: '',
    clinic_pincode: '',
    clinic_fax: '',
    clinic_tel1: '',
    clinic_tel2: '',
    clinic_mobile: '',
    clinic_email: '',
    clinic_website: '',
    is_active: true
});

const fetchMasterData = async () => {
    try {
        const [prefixesRes, gendersRes, bloodGroupsRes, qualificationsRes, departmentsRes, countriesRes, statesRes, districtsRes, citiesRes] = await Promise.all([
            axios.get('/api/prefixes-active'),
            axios.get('/api/genders-active'),
            axios.get('/api/blood-groups-active'),
            axios.get('/api/qualifications-active'),
            axios.get('/api/departments'),
            axios.get('/api/countries-active'),
            axios.get('/api/states-active'),
            axios.get('/api/districts-active'),
            axios.get('/api/cities-active')
        ]);
        prefixes.value = prefixesRes.data;
        genders.value = gendersRes.data;
        bloodGroups.value = bloodGroupsRes.data;
        qualifications.value = qualificationsRes.data;
        departments.value = departmentsRes.data;
        countries.value = countriesRes.data;
        allStates.value = statesRes.data;
        allDistricts.value = districtsRes.data;
        allCities.value = citiesRes.data;
    } catch (error) {
        console.error('Error fetching master data:', error);
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        const response = await axios.get('/api/reference-doctors', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const onResCountryChange = () => {
    form.res_state_id = '';
    form.res_district_id = '';
    form.res_city_id = '';
};

const onResStateChange = () => {
    form.res_district_id = '';
    form.res_city_id = '';
};

const onResDistrictChange = () => {
    form.res_city_id = '';
};

const onClinicCountryChange = () => {
    form.clinic_state_id = '';
    form.clinic_district_id = '';
    form.clinic_city_id = '';
};

const onClinicStateChange = () => {
    form.clinic_district_id = '';
    form.clinic_city_id = '';
};

const onClinicDistrictChange = () => {
    form.clinic_city_id = '';
};

const resetForm = () => {
    Object.assign(form, {
        prefix_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        gender_id: '',
        blood_group_id: '',
        qualification_id: '',
        department_id: '',
        registration_no: '',
        practice_no: '',
        dob: '',
        clinic_name: '',
        mobile: '',
        email: '',
        res_address_line1: '',
        res_address_line2: '',
        res_country_id: '',
        res_state_id: '',
        res_district_id: '',
        res_city_id: '',
        res_pincode: '',
        res_fax: '',
        res_tel1: '',
        res_tel2: '',
        res_mobile: '',
        res_email: '',
        res_website: '',
        clinic_address_line1: '',
        clinic_address_line2: '',
        clinic_country_id: '',
        clinic_state_id: '',
        clinic_district_id: '',
        clinic_city_id: '',
        clinic_pincode: '',
        clinic_fax: '',
        clinic_tel1: '',
        clinic_tel2: '',
        clinic_mobile: '',
        clinic_email: '',
        clinic_website: '',
        is_active: true
    });
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        Object.assign(form, {
            prefix_id: item.prefix_id || '',
            first_name: item.first_name || '',
            middle_name: item.middle_name || '',
            last_name: item.last_name || '',
            gender_id: item.gender_id || '',
            blood_group_id: item.blood_group_id || '',
            qualification_id: item.qualification_id || '',
            department_id: item.department_id || '',
            registration_no: item.registration_no || '',
            practice_no: item.practice_no || '',
            dob: item.dob ? item.dob.split('T')[0] : '',
            clinic_name: item.clinic_name || '',
            mobile: item.mobile || '',
            email: item.email || '',
            res_address_line1: item.res_address_line1 || '',
            res_address_line2: item.res_address_line2 || '',
            res_country_id: item.res_country_id || '',
            res_state_id: item.res_state_id || '',
            res_district_id: item.res_district_id || '',
            res_city_id: item.res_city_id || '',
            res_pincode: item.res_pincode || '',
            res_fax: item.res_fax || '',
            res_tel1: item.res_tel1 || '',
            res_tel2: item.res_tel2 || '',
            res_mobile: item.res_mobile || '',
            res_email: item.res_email || '',
            res_website: item.res_website || '',
            clinic_address_line1: item.clinic_address_line1 || '',
            clinic_address_line2: item.clinic_address_line2 || '',
            clinic_country_id: item.clinic_country_id || '',
            clinic_state_id: item.clinic_state_id || '',
            clinic_district_id: item.clinic_district_id || '',
            clinic_city_id: item.clinic_city_id || '',
            clinic_pincode: item.clinic_pincode || '',
            clinic_fax: item.clinic_fax || '',
            clinic_tel1: item.clinic_tel1 || '',
            clinic_tel2: item.clinic_tel2 || '',
            clinic_mobile: item.clinic_mobile || '',
            clinic_email: item.clinic_email || '',
            clinic_website: item.clinic_website || '',
            is_active: item.is_active
        });
    } else {
        resetForm();
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        const payload = { ...form };
        // Convert empty strings to null for IDs
        ['prefix_id', 'gender_id', 'blood_group_id', 'qualification_id', 'department_id',
         'res_country_id', 'res_state_id', 'res_district_id', 'res_city_id',
         'clinic_country_id', 'clinic_state_id', 'clinic_district_id', 'clinic_city_id'].forEach(key => {
            if (payload[key] === '') payload[key] = null;
        });

        if (editingItem.value) {
            await axios.put(`/api/reference-doctors/${editingItem.value.reference_doctor_id}`, payload);
        } else {
            await axios.post('/api/reference-doctors', payload);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.doctor_name}"?`)) return;
    try {
        await axios.delete(`/api/reference-doctors/${item.reference_doctor_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchMasterData();
    fetchData();
});
</script>

<style scoped>
/* No custom styles needed - using global responsive styles from dreams-emr.css */
</style>
