<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">{{ isEdit ? 'Edit Hospital' : 'Add Hospital' }}</h5>
                <p class="text-muted mb-0">{{ isEdit ? 'Update hospital information' : 'Onboard a new hospital/clinic' }}</p>
            </div>
        </div>

        <form @submit.prevent="saveHospital">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Hospital Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Hospital Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Hospital Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.code" required placeholder="e.g., HOSP001">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.type" required>
                                        <option value="general">General Hospital</option>
                                        <option value="clinic">Clinic</option>
                                        <option value="opd_center">OPD Center</option>
                                        <option value="ipd_center">IPD Center</option>
                                        <option value="diagnostic_center">Diagnostic Center</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" v-model="form.phone">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="form.email">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" v-model="form.address" rows="2"></textarea>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" v-model="form.city">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" class="form-control" v-model="form.state">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" class="form-control" v-model="form.pincode">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">License Number</label>
                                    <input type="text" class="form-control" v-model="form.license_number">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">License Expiry</label>
                                    <input type="date" class="form-control" v-model="form.license_expiry">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tax ID / GST</label>
                                    <input type="text" class="form-control" v-model="form.tax_id">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" v-model="form.website" placeholder="https://">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Account (Only for new hospitals) -->
                    <div class="card mb-4" v-if="!isEdit">
                        <div class="card-header">
                            <h6 class="mb-0">Admin Account</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Admin Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.admin_username" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" v-model="form.admin_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" v-model="form.admin_full_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admin Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" v-model="form.admin_email" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Global Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Global Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.currency" required>
                                    <option value="INR">INR - Indian Rupee (Rs.)</option>
                                    <option value="USD">USD - US Dollar ($)</option>
                                    <option value="EUR">EUR - Euro (&#8364;)</option>
                                    <option value="GBP">GBP - British Pound (&#163;)</option>
                                    <option value="AED">AED - UAE Dirham (AED)</option>
                                    <option value="SAR">SAR - Saudi Riyal (SAR)</option>
                                    <option value="BDT">BDT - Bangladeshi Taka (&#2547;)</option>
                                    <option value="NPR">NPR - Nepalese Rupee (Rs.)</option>
                                    <option value="LKR">LKR - Sri Lankan Rupee (Rs.)</option>
                                    <option value="PKR">PKR - Pakistani Rupee (Rs.)</option>
                                    <option value="MYR">MYR - Malaysian Ringgit (RM)</option>
                                    <option value="SGD">SGD - Singapore Dollar (S$)</option>
                                    <option value="THB">THB - Thai Baht (&#3647;)</option>
                                    <option value="PHP">PHP - Philippine Peso (&#8369;)</option>
                                    <option value="IDR">IDR - Indonesian Rupiah (Rp)</option>
                                    <option value="AUD">AUD - Australian Dollar (A$)</option>
                                    <option value="CAD">CAD - Canadian Dollar (C$)</option>
                                    <option value="ZAR">ZAR - South African Rand (R)</option>
                                    <option value="NGN">NGN - Nigerian Naira (&#8358;)</option>
                                    <option value="KES">KES - Kenyan Shilling (KSh)</option>
                                </select>
                                <small class="text-muted">This currency will be used for all billing and payments</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date Format</label>
                                <select class="form-select" v-model="form.date_format">
                                    <option value="DD/MM/YYYY">DD/MM/YYYY (31/12/2026)</option>
                                    <option value="MM/DD/YYYY">MM/DD/YYYY (12/31/2026)</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD (2026-12-31)</option>
                                    <option value="DD-MMM-YYYY">DD-MMM-YYYY (31-Dec-2026)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Format</label>
                                <select class="form-select" v-model="form.time_format">
                                    <option value="12h">12 Hour (2:30 PM)</option>
                                    <option value="24h">24 Hour (14:30)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Subscription</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Plan</label>
                                <select class="form-select" v-model="form.subscription_plan">
                                    <option value="basic">Basic</option>
                                    <option value="standard">Standard</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" v-model="form.subscription_start">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" v-model="form.subscription_end">
                                <small class="text-muted">Leave empty for unlimited</small>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="isActive">
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <!-- Stats (Edit mode only) -->
                    <div class="card mb-4" v-if="isEdit && hospitalStats">
                        <div class="card-header">
                            <h6 class="mb-0">Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Users</span>
                                <strong>{{ hospitalStats.total_users }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Patients</span>
                                <strong>{{ hospitalStats.total_patients }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Doctors</span>
                                <strong>{{ hospitalStats.total_doctors }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Departments</span>
                                <strong>{{ hospitalStats.total_departments }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Update' : 'Create' }} Hospital
                        </button>
                        <router-link to="/hospitals" class="btn btn-light">Cancel</router-link>
                    </div>
                </div>
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
const isEdit = computed(() => !!route.params.id);

const saving = ref(false);
const hospitalStats = ref(null);

const form = reactive({
    code: '',
    name: '',
    type: 'general',
    address: '',
    city: '',
    state: '',
    country: 'India',
    pincode: '',
    phone: '',
    email: '',
    website: '',
    license_number: '',
    license_expiry: '',
    tax_id: '',
    // Global Settings
    currency: 'INR',
    date_format: 'DD/MM/YYYY',
    time_format: '12h',
    // Subscription
    subscription_plan: 'basic',
    subscription_start: new Date().toISOString().split('T')[0],
    subscription_end: '',
    is_active: true,
    // Admin fields (only for create)
    admin_username: '',
    admin_password: '',
    admin_full_name: '',
    admin_email: '',
});

const fetchHospital = async () => {
    if (route.params.id) {
        const response = await axios.get(`/api/hospitals/${route.params.id}`);
        const hospital = response.data;

        const settings = hospital.settings || {};
        Object.assign(form, {
            code: hospital.code,
            name: hospital.name,
            type: hospital.type,
            address: hospital.address || '',
            city: hospital.city || '',
            state: hospital.state || '',
            country: hospital.country || 'India',
            pincode: hospital.pincode || '',
            phone: hospital.phone || '',
            email: hospital.email || '',
            website: hospital.website || '',
            license_number: hospital.license_number || '',
            license_expiry: hospital.license_expiry || '',
            tax_id: hospital.tax_id || '',
            // Global Settings
            currency: settings.currency || 'INR',
            date_format: settings.date_format || 'DD/MM/YYYY',
            time_format: settings.time_format || '12h',
            // Subscription
            subscription_plan: hospital.subscription_plan || 'basic',
            subscription_start: hospital.subscription_start || '',
            subscription_end: hospital.subscription_end || '',
            is_active: hospital.is_active,
        });

        hospitalStats.value = hospital.stats;
    }
};

const saveHospital = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/hospitals/${route.params.id}`, form);
        } else {
            await axios.post('/api/hospitals', form);
        }
        router.push('/hospitals');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving hospital');
    }
    saving.value = false;
};

onMounted(fetchHospital);
</script>
