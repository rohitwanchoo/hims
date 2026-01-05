<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-bell me-2"></i>Notification Settings</h4>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'gateways' }" @click="activeTab = 'gateways'" href="#">SMS Gateways</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'templates' }" @click="activeTab = 'templates'" href="#">Templates</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'logs' }" @click="activeTab = 'logs'" href="#">Logs</a>
            </li>
        </ul>

        <!-- SMS Gateways -->
        <div v-if="activeTab === 'gateways'">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary" @click="showGatewayForm = true">
                    <i class="bi bi-plus-lg"></i> Add Gateway
                </button>
            </div>
            <div class="row">
                <div v-for="gateway in gateways" :key="gateway.gateway_id" class="col-md-4 mb-4">
                    <div class="card" :class="{ 'border-success': gateway.is_default }">
                        <div class="card-header d-flex justify-content-between">
                            <span>
                                <strong>{{ gateway.gateway_name }}</strong>
                                <span v-if="gateway.is_default" class="badge bg-success ms-2">Default</span>
                            </span>
                            <span class="badge" :class="gateway.is_active ? 'bg-success' : 'bg-secondary'">
                                {{ gateway.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="card-body">
                            <p><strong>Provider:</strong> {{ gateway.provider }}</p>
                            <p><strong>Sender ID:</strong> {{ gateway.sender_id }}</p>
                        </div>
                        <div class="card-footer">
                            <button @click="testGateway(gateway)" class="btn btn-sm btn-outline-info me-1">
                                <i class="bi bi-send"></i> Test
                            </button>
                            <button @click="editGateway(gateway)" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates -->
        <div v-if="activeTab === 'templates'">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary" @click="showTemplateForm = true">
                    <i class="bi bi-plus-lg"></i> Add Template
                </button>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Channel</th>
                                <th>Subject/Preview</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="template in templates" :key="template.template_id">
                                <td><code>{{ template.template_code }}</code></td>
                                <td>{{ template.notification_type }}</td>
                                <td>
                                    <span class="badge" :class="getChannelClass(template.channel)">
                                        {{ template.channel }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="template.channel === 'email'">{{ template.email_subject }}</span>
                                    <span v-else>{{ truncate(template.sms_template, 50) }}</span>
                                </td>
                                <td>
                                    <span class="badge" :class="template.is_active ? 'bg-success' : 'bg-secondary'">
                                        {{ template.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button @click="editTemplate(template)" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div v-if="activeTab === 'logs'">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select v-model="logFilters.channel" class="form-select" @change="loadLogs">
                                <option value="">All Channels</option>
                                <option value="sms">SMS</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select v-model="logFilters.status" class="form-select" @change="loadLogs">
                                <option value="">All Status</option>
                                <option value="sent">Sent</option>
                                <option value="delivered">Delivered</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" v-model="logFilters.search" class="form-control" placeholder="Search recipient..." @input="loadLogs">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>Recipient</th>
                                <th>Channel</th>
                                <th>Message</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs" :key="log.log_id">
                                <td>{{ formatDateTime(log.created_at) }}</td>
                                <td>
                                    {{ log.recipient_name }}<br>
                                    <small class="text-muted">{{ log.recipient_contact }}</small>
                                </td>
                                <td><span class="badge" :class="getChannelClass(log.channel)">{{ log.channel }}</span></td>
                                <td>{{ truncate(log.message, 40) }}</td>
                                <td>
                                    <span class="badge" :class="getStatusClass(log.status)">{{ log.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('gateways');
const gateways = ref([]);
const templates = ref([]);
const logs = ref([]);
const showGatewayForm = ref(false);
const showTemplateForm = ref(false);
const logFilters = ref({ channel: '', status: '', search: '' });

const loadGateways = async () => {
    try {
        const response = await axios.get('/api/notifications/gateways');
        gateways.value = response.data.gateways || [];
    } catch (error) {
        console.error('Failed to load gateways:', error);
    }
};

const loadTemplates = async () => {
    try {
        const response = await axios.get('/api/notifications/templates');
        templates.value = response.data.templates || [];
    } catch (error) {
        console.error('Failed to load templates:', error);
    }
};

const loadLogs = async () => {
    try {
        const params = new URLSearchParams();
        if (logFilters.value.channel) params.append('channel', logFilters.value.channel);
        if (logFilters.value.status) params.append('status', logFilters.value.status);
        if (logFilters.value.search) params.append('search', logFilters.value.search);

        const response = await axios.get(`/api/notifications/logs?${params}`);
        logs.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load logs:', error);
    }
};

const testGateway = async (gateway) => {
    const mobile = prompt('Enter mobile number to test:');
    if (mobile) {
        try {
            await axios.post(`/api/notifications/gateways/${gateway.gateway_id}/test`, {
                mobile,
                message: 'Test message from HIMS'
            });
            alert('Test message sent!');
        } catch (error) {
            alert('Failed to send test message');
        }
    }
};

const editGateway = (gateway) => {
    // Show edit form
};

const editTemplate = (template) => {
    // Show edit form
};

const formatDateTime = (dt) => new Date(dt).toLocaleString();
const truncate = (text, len) => text?.length > len ? text.substring(0, len) + '...' : text;

const getChannelClass = (channel) => {
    return channel === 'sms' ? 'bg-info' : channel === 'email' ? 'bg-primary' : 'bg-secondary';
};

const getStatusClass = (status) => {
    const classes = { 'sent': 'bg-info', 'delivered': 'bg-success', 'failed': 'bg-danger', 'queued': 'bg-warning' };
    return classes[status] || 'bg-secondary';
};

onMounted(() => {
    loadGateways();
    loadTemplates();
    loadLogs();
});
</script>
