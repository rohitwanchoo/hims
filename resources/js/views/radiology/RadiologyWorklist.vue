<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-list-check me-2"></i>Radiology Worklist</h4>
            <div>
                <input type="date" v-model="selectedDate" class="form-control d-inline-block w-auto" @change="loadWorklist">
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3>{{ stats.total }}</h3>
                        <small>Total Today</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h3>{{ stats.pending }}</h3>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h3>{{ stats.in_progress }}</h3>
                        <small>In Progress</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h3>{{ stats.completed }}</h3>
                        <small>Completed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Worklist Table -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'" href="#">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'in_progress' }" @click="activeTab = 'in_progress'" href="#">In Progress</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'completed' }" @click="activeTab = 'completed'" href="#">Completed</a>
                    </li>
                </ul>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Modality</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in filteredWorklist" :key="item.detail_id">
                            <td>{{ item.scheduled_datetime ? formatTime(item.scheduled_datetime) : '-' }}</td>
                            <td>{{ item.order?.patient?.patient_name }}</td>
                            <td>{{ item.test?.test_name }}</td>
                            <td>{{ item.test?.modality?.modality_name }}</td>
                            <td>
                                <span class="badge" :class="getPriorityClass(item.order?.priority)">
                                    {{ item.order?.priority }}
                                </span>
                            </td>
                            <td>
                                <button v-if="item.status === 'pending'" @click="startExam(item)" class="btn btn-sm btn-primary">
                                    Start Exam
                                </button>
                                <button v-else-if="item.status === 'in_progress'" @click="completeExam(item)" class="btn btn-sm btn-success">
                                    Complete
                                </button>
                                <router-link v-else :to="`/radiology/reports/${item.report_id}`" class="btn btn-sm btn-outline-primary">
                                    View Report
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const worklist = ref([]);
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const activeTab = ref('pending');

const stats = computed(() => ({
    total: worklist.value.length,
    pending: worklist.value.filter(w => w.status === 'pending').length,
    in_progress: worklist.value.filter(w => w.status === 'in_progress').length,
    completed: worklist.value.filter(w => w.status === 'completed').length
}));

const filteredWorklist = computed(() => {
    return worklist.value.filter(w => w.status === activeTab.value);
});

const loadWorklist = async () => {
    try {
        const response = await axios.get(`/api/radiology/orders/worklist?date=${selectedDate.value}`);
        worklist.value = response.data.worklist || [];
    } catch (error) {
        console.error('Failed to load worklist:', error);
    }
};

const formatTime = (datetime) => new Date(datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const getPriorityClass = (priority) => {
    const classes = { 'routine': 'bg-secondary', 'urgent': 'bg-warning', 'stat': 'bg-danger' };
    return classes[priority] || 'bg-secondary';
};

const startExam = async (item) => {
    try {
        await axios.post(`/api/radiology/orders/details/${item.detail_id}/status`, { status: 'in_progress' });
        loadWorklist();
    } catch (error) {
        console.error('Failed to start exam:', error);
    }
};

const completeExam = async (item) => {
    try {
        await axios.post(`/api/radiology/orders/details/${item.detail_id}/status`, { status: 'completed' });
        loadWorklist();
    } catch (error) {
        console.error('Failed to complete exam:', error);
    }
};

onMounted(loadWorklist);
</script>
