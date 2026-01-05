<template>
    <div>
        <h4 class="mb-4">Settings</h4>

        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'general' }"
                           @click.prevent="activeTab = 'general'">
                            <i class="bi bi-gear me-2"></i> General
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'hospital' }"
                           @click.prevent="activeTab = 'hospital'">
                            <i class="bi bi-hospital me-2"></i> Hospital Info
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'billing' }"
                           @click.prevent="activeTab = 'billing'">
                            <i class="bi bi-receipt me-2"></i> Billing
                        </a>
                        <a href="#" class="list-group-item list-group-item-action"
                           :class="{ active: activeTab === 'users' }"
                           @click.prevent="activeTab = 'users'">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- General Settings -->
                <div class="card" v-if="activeTab === 'general'">
                    <div class="card-header">
                        <h6 class="mb-0">General Settings</h6>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveSettings">
                            <div class="mb-3">
                                <label class="form-label">Date Format</label>
                                <select class="form-select" v-model="settings.date_format">
                                    <option value="Y-m-d">YYYY-MM-DD</option>
                                    <option value="d/m/Y">DD/MM/YYYY</option>
                                    <option value="m/d/Y">MM/DD/YYYY</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select class="form-select" v-model="settings.currency">
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                    <option value="INR">INR (₹)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Timezone</label>
                                <select class="form-select" v-model="settings.timezone">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">America/New_York</option>
                                    <option value="Europe/London">Europe/London</option>
                                    <option value="Asia/Kolkata">Asia/Kolkata</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Hospital Info -->
                <div class="card" v-if="activeTab === 'hospital'">
                    <div class="card-header">
                        <h6 class="mb-0">Hospital Information</h6>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveSettings">
                            <div class="mb-3">
                                <label class="form-label">Hospital Name</label>
                                <input type="text" class="form-control" v-model="settings.hospital_name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" rows="2" v-model="settings.hospital_address"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control" v-model="settings.hospital_phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="settings.hospital_email">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Billing Settings -->
                <div class="card" v-if="activeTab === 'billing'">
                    <div class="card-header">
                        <h6 class="mb-0">Billing Settings</h6>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="saveSettings">
                            <div class="mb-3">
                                <label class="form-label">Invoice Prefix</label>
                                <input type="text" class="form-control" v-model="settings.invoice_prefix" placeholder="INV-">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tax Rate (%)</label>
                                <input type="number" class="form-control" v-model="settings.tax_rate" step="0.01">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="settings.auto_generate_bill" id="autoGenBill">
                                    <label class="form-check-label" for="autoGenBill">
                                        Auto-generate bill on discharge
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Users -->
                <div class="card" v-if="activeTab === 'users'">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Users</h6>
                        <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Add User</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.id">
                                    <td>{{ user.username }}</td>
                                    <td>{{ user.full_name }}</td>
                                    <td><span class="badge bg-info">{{ user.role }}</span></td>
                                    <td>
                                        <span class="badge" :class="user.is_active ? 'bg-success' : 'bg-secondary'">
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" v-if="user.role !== 'admin'"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('general');
const saving = ref(false);
const settings = ref({
    date_format: 'Y-m-d',
    currency: 'USD',
    timezone: 'UTC',
    hospital_name: '',
    hospital_address: '',
    hospital_phone: '',
    hospital_email: '',
    invoice_prefix: 'INV-',
    tax_rate: 0,
    auto_generate_bill: false
});
const users = ref([]);

onMounted(async () => {
    try {
        const [settingsRes, usersRes] = await Promise.all([
            axios.get('/api/settings'),
            axios.get('/api/users')
        ]);

        // Convert settings array to object
        if (Array.isArray(settingsRes.data)) {
            settingsRes.data.forEach(s => {
                settings.value[s.setting_key] = s.setting_value;
            });
        }

        users.value = usersRes.data.data || usersRes.data;
    } catch (error) {
        console.error('Error loading settings:', error);
    }
});

const saveSettings = async () => {
    saving.value = true;
    try {
        await axios.post('/api/settings', settings.value);
        alert('Settings saved successfully');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving settings');
    } finally {
        saving.value = false;
    }
};
</script>
