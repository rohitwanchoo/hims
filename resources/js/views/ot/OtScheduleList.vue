<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-calendar2-week me-2"></i>OT Schedule</h4>
            <router-link to="/ot/schedules/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Schedule Surgery
            </router-link>
        </div>

        <!-- Date Navigation -->
        <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <button class="btn btn-outline-secondary" @click="changeDate(-1)">
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <div>
                    <input type="date" v-model="selectedDate" class="form-control form-control-lg text-center" @change="loadSchedules">
                </div>
                <button class="btn btn-outline-secondary" @click="changeDate(1)">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- OT Rooms -->
        <div v-for="(schedules, otId) in groupedSchedules" :key="otId" class="card mb-4">
            <div class="card-header bg-dark text-white">
                <strong>{{ getOtName(otId) }}</strong>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Time</th>
                            <th>Patient</th>
                            <th>Surgery</th>
                            <th>Surgeon</th>
                            <th>Anesthetist</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="schedule in schedules" :key="schedule.schedule_id" :class="getRowClass(schedule.status)">
                            <td><strong>{{ schedule.scheduled_start_time }}</strong></td>
                            <td>{{ schedule.patient?.patient_name }}</td>
                            <td>{{ schedule.surgery_type?.surgery_name }}</td>
                            <td>{{ schedule.surgeon?.full_name }}</td>
                            <td>{{ schedule.anesthetist?.full_name || '-' }}</td>
                            <td>
                                <span class="badge" :class="getStatusClass(schedule.status)">
                                    {{ schedule.status }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/ot/schedules/${schedule.schedule_id}`" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <button v-if="schedule.status === 'scheduled'" @click="confirmSchedule(schedule)" class="btn btn-outline-success">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="schedule.status === 'confirmed'" @click="startProcedure(schedule)" class="btn btn-success">
                                        Start
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="Object.keys(groupedSchedules).length === 0" class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-calendar-x display-4"></i>
                <p class="mt-3">No surgeries scheduled for this date</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const schedules = ref([]);
const theaters = ref([]);
const selectedDate = ref(new Date().toISOString().split('T')[0]);

const groupedSchedules = computed(() => {
    const grouped = {};
    schedules.value.forEach(s => {
        if (!grouped[s.ot_id]) grouped[s.ot_id] = [];
        grouped[s.ot_id].push(s);
    });
    return grouped;
});

const loadSchedules = async () => {
    try {
        const response = await axios.get(`/api/ot/schedules?date=${selectedDate.value}`);
        schedules.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load schedules:', error);
    }
};

const loadTheaters = async () => {
    try {
        const response = await axios.get('/api/ot/theaters');
        theaters.value = response.data.theaters || [];
    } catch (error) {
        console.error('Failed to load theaters:', error);
    }
};

const changeDate = (days) => {
    const date = new Date(selectedDate.value);
    date.setDate(date.getDate() + days);
    selectedDate.value = date.toISOString().split('T')[0];
    loadSchedules();
};

const getOtName = (otId) => {
    const ot = theaters.value.find(t => t.ot_id == otId);
    return ot ? `${ot.ot_name} (${ot.ot_code})` : `OT ${otId}`;
};

const getStatusClass = (status) => {
    const classes = {
        'scheduled': 'bg-secondary',
        'confirmed': 'bg-info',
        'in_progress': 'bg-warning',
        'completed': 'bg-success',
        'postponed': 'bg-dark',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const getRowClass = (status) => {
    if (status === 'in_progress') return 'table-warning';
    if (status === 'completed') return 'table-success';
    if (status === 'cancelled') return 'table-danger';
    return '';
};

const confirmSchedule = async (schedule) => {
    try {
        await axios.post(`/api/ot/schedules/${schedule.schedule_id}/status`, { status: 'confirmed' });
        loadSchedules();
    } catch (error) {
        console.error('Failed to confirm schedule:', error);
    }
};

const startProcedure = async (schedule) => {
    // Navigate to start procedure form
};

onMounted(() => {
    loadSchedules();
    loadTheaters();
});
</script>
