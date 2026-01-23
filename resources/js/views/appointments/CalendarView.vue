<template>
    <div class="calendar-view">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Appointment Calendar</h5>
                <p class="text-muted mb-0">View and manage appointments</p>
            </div>
            <div class="d-flex gap-2">
                <router-link to="/appointments/new" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Appointment
                </router-link>
            </div>
        </div>

        <!-- Calendar Controls -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <!-- View Type -->
                    <div class="col-md-3">
                        <div class="btn-group w-100" role="group">
                            <button type="button"
                                    class="btn btn-sm"
                                    :class="view === 'month' ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="view = 'month'">
                                <i class="bi bi-calendar3 me-1"></i> Month
                            </button>
                            <button type="button"
                                    class="btn btn-sm"
                                    :class="view === 'week' ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="view = 'week'">
                                <i class="bi bi-calendar-week me-1"></i> Week
                            </button>
                            <button type="button"
                                    class="btn btn-sm"
                                    :class="view === 'day' ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="view = 'day'">
                                <i class="bi bi-calendar-day me-1"></i> Day
                            </button>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="col-md-6">
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <button class="btn btn-outline-secondary btn-sm" @click="previousPeriod">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" @click="today">
                                Today
                            </button>
                            <h6 class="mb-0 fw-semibold">{{ currentDateDisplay }}</h6>
                            <button class="btn btn-outline-secondary btn-sm" @click="nextPeriod">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" v-model="filterDepartment" @change="fetchAppointments">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Legend -->
                <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center gap-1">
                        <span class="status-badge bg-success"></span>
                        <small class="text-muted">Completed</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="status-badge bg-primary"></span>
                        <small class="text-muted">Confirmed</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="status-badge bg-info"></span>
                        <small class="text-muted">Checked In</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="status-badge bg-warning"></span>
                        <small class="text-muted">Scheduled</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="status-badge bg-danger"></span>
                        <small class="text-muted">Cancelled</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading appointments...</p>
        </div>

        <!-- Calendar Views -->
        <div v-else>
            <!-- Month View -->
            <div v-if="view === 'month'" class="card">
                <div class="calendar-month">
                    <!-- Weekday Headers -->
                    <div class="calendar-weekdays">
                        <div v-for="day in weekdays" :key="day" class="calendar-weekday">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Calendar Days -->
                    <div class="calendar-grid">
                        <div v-for="day in monthDays"
                             :key="day.date"
                             class="calendar-day"
                             :class="{
                                 'other-month': !day.currentMonth,
                                 'today': day.isToday,
                                 'has-appointments': day.appointments.length > 0
                             }"
                             @click="selectDate(day)">
                            <div class="day-number">{{ day.day }}</div>
                            <div class="day-appointments">
                                <div v-for="(appt, index) in day.appointments.slice(0, 3)"
                                     :key="appt.appointment_id"
                                     class="appointment-badge"
                                     :class="`status-${appt.status}`"
                                     @click.stop="viewAppointment(appt)">
                                    <small>{{ formatTime(appt.appointment_time) }} - {{ appt.patient?.patient_name }}</small>
                                </div>
                                <div v-if="day.appointments.length > 3" class="more-badge">
                                    +{{ day.appointments.length - 3 }} more
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Week View -->
            <div v-if="view === 'week'" class="card">
                <div class="calendar-week">
                    <!-- Time Column -->
                    <div class="time-column">
                        <div class="time-slot-header"></div>
                        <div v-for="hour in timeSlots" :key="hour" class="time-slot">
                            {{ hour }}
                        </div>
                    </div>

                    <!-- Days Columns -->
                    <div v-for="day in weekDays" :key="day.date" class="day-column">
                        <div class="day-header" :class="{ 'today': day.isToday }">
                            <div class="day-name">{{ day.dayName }}</div>
                            <div class="day-date">{{ day.day }}</div>
                            <div class="appointment-count">{{ day.appointments.length }} appt</div>
                        </div>
                        <div class="day-timeline">
                            <div v-for="appt in day.appointments"
                                 :key="appt.appointment_id"
                                 class="timeline-appointment"
                                 :class="`status-${appt.status}`"
                                 :style="getAppointmentStyle(appt)"
                                 @click="viewAppointment(appt)">
                                <div class="appointment-time">{{ formatTime(appt.appointment_time) }}</div>
                                <div class="appointment-patient">{{ appt.patient?.patient_name }}</div>
                                <div class="appointment-doctor">{{ appt.doctor?.full_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Day View -->
            <div v-if="view === 'day'" class="card">
                <div class="card-body">
                    <div class="row">
                        <!-- Timeline -->
                        <div class="col-md-8">
                            <div class="day-view-timeline">
                                <div v-for="hour in timeSlots" :key="hour" class="time-row">
                                    <div class="time-label">{{ hour }}</div>
                                    <div class="time-content">
                                        <div v-for="appt in getAppointmentsForHour(hour)"
                                             :key="appt.appointment_id"
                                             class="day-appointment"
                                             :class="`status-${appt.status}`"
                                             @click="viewAppointment(appt)">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong>{{ formatTime(appt.appointment_time) }}</strong>
                                                <span class="badge" :class="`bg-${getStatusColor(appt.status)}`">
                                                    {{ appt.status }}
                                                </span>
                                            </div>
                                            <div class="mb-1">
                                                <i class="bi bi-person me-1"></i>
                                                <strong>{{ appt.patient?.patient_name }}</strong>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="bi bi-person-badge me-1"></i>
                                                Dr. {{ appt.doctor?.full_name }}
                                            </div>
                                            <div class="text-muted small">
                                                <i class="bi bi-building me-1"></i>
                                                {{ appt.department?.department_name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Sidebar -->
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3">
                                        <i class="bi bi-calendar-check me-2"></i>
                                        {{ formatDate(currentDate) }}
                                    </h6>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-label">Total</div>
                                            <div class="stat-value">{{ dayAppointments.length }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Completed</div>
                                            <div class="stat-value text-success">
                                                {{ dayAppointments.filter(a => a.status === 'completed').length }}
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Scheduled</div>
                                            <div class="stat-value text-warning">
                                                {{ dayAppointments.filter(a => a.status === 'scheduled').length }}
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">Cancelled</div>
                                            <div class="stat-value text-danger">
                                                {{ dayAppointments.filter(a => a.status === 'cancelled').length }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment Details Modal -->
        <div class="modal fade" id="appointmentModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" v-if="selectedAppointment">
                    <div class="modal-header">
                        <h5 class="modal-title">Appointment Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small">Appointment Number</label>
                                <div class="fw-semibold">{{ selectedAppointment.appointment_number }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Status</label>
                                <div>
                                    <span class="badge" :class="`bg-${getStatusColor(selectedAppointment.status)}`">
                                        {{ selectedAppointment.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Patient</label>
                                <div class="fw-semibold">{{ selectedAppointment.patient?.patient_name }}</div>
                                <small class="text-muted">PCD: {{ selectedAppointment.patient?.pcd }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Doctor</label>
                                <div class="fw-semibold">Dr. {{ selectedAppointment.doctor?.full_name }}</div>
                                <small class="text-muted">{{ selectedAppointment.doctor?.specialization }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Date & Time</label>
                                <div class="fw-semibold">
                                    {{ formatDate(selectedAppointment.appointment_date) }}
                                </div>
                                <small class="text-muted">{{ formatTime(selectedAppointment.appointment_time) }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small">Department</label>
                                <div>{{ selectedAppointment.department?.department_name }}</div>
                            </div>
                            <div class="col-12" v-if="selectedAppointment.reason">
                                <label class="text-muted small">Reason for Visit</label>
                                <div>{{ selectedAppointment.reason }}</div>
                            </div>
                            <div class="col-12" v-if="selectedAppointment.notes">
                                <label class="text-muted small">Notes</label>
                                <div>{{ selectedAppointment.notes }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <router-link
                            :to="`/appointments/${selectedAppointment.appointment_id}/edit`"
                            class="btn btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Modal } from 'bootstrap';

const router = useRouter();
const view = ref('month');
const currentDate = ref(new Date());
const appointments = ref([]);
const departments = ref([]);
const filterDepartment = ref('');
const loading = ref(false);
const selectedAppointment = ref(null);
const modalRef = ref(null);
let modal = null;

const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const timeSlots = [
    '8:00 AM', '9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
    '1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM', '6:00 PM'
];

// Computed
const currentDateDisplay = computed(() => {
    const date = currentDate.value;
    const options = view.value === 'day'
        ? { month: 'long', day: 'numeric', year: 'numeric' }
        : { month: 'long', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
});

const monthDays = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();

    const days = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Previous month days
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        const day = prevMonthLastDay - i;
        const date = new Date(year, month - 1, day);
        const dateStr = formatDateToYMD(date);
        days.push({
            day,
            date: dateStr,
            currentMonth: false,
            isToday: false,
            appointments: getAppointmentsForDate(dateStr)
        });
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(year, month, day);
        const dateStr = formatDateToYMD(date);
        days.push({
            day,
            date: dateStr,
            currentMonth: true,
            isToday: date.getTime() === today.getTime(),
            appointments: getAppointmentsForDate(dateStr)
        });
    }

    // Next month days
    const remainingDays = 42 - days.length;
    for (let day = 1; day <= remainingDays; day++) {
        const date = new Date(year, month + 1, day);
        const dateStr = formatDateToYMD(date);
        days.push({
            day,
            date: dateStr,
            currentMonth: false,
            isToday: false,
            appointments: getAppointmentsForDate(dateStr)
        });
    }

    return days;
});

const weekDays = computed(() => {
    const days = [];
    const startOfWeek = new Date(currentDate.value);
    startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 0; i < 7; i++) {
        const date = new Date(startOfWeek);
        date.setDate(startOfWeek.getDate() + i);
        const dateStr = formatDateToYMD(date);
        days.push({
            day: date.getDate(),
            dayName: weekdays[date.getDay()],
            date: dateStr,
            isToday: date.getTime() === today.getTime(),
            appointments: getAppointmentsForDate(dateStr)
        });
    }

    return days;
});

const dayAppointments = computed(() => {
    const dateStr = formatDateToYMD(currentDate.value);
    return getAppointmentsForDate(dateStr);
});

// Methods
const fetchAppointments = async () => {
    loading.value = true;
    try {
        let startDate, endDate;

        if (view.value === 'month') {
            const year = currentDate.value.getFullYear();
            const month = currentDate.value.getMonth();
            startDate = formatDateToYMD(new Date(year, month, 1));
            endDate = formatDateToYMD(new Date(year, month + 1, 0));
        } else if (view.value === 'week') {
            const start = new Date(currentDate.value);
            start.setDate(start.getDate() - start.getDay());
            startDate = formatDateToYMD(start);
            const end = new Date(start);
            end.setDate(start.getDate() + 6);
            endDate = formatDateToYMD(end);
        } else {
            startDate = endDate = formatDateToYMD(currentDate.value);
        }

        const params = {
            start_date: startDate,
            end_date: endDate,
            per_page: 1000
        };

        if (filterDepartment.value) {
            params.department_id = filterDepartment.value;
        }

        const response = await axios.get('/api/appointments', { params });
        const responseData = response.data.appointments || response.data.data?.data || response.data.data || response.data;
        appointments.value = Array.isArray(responseData) ? responseData : [];

    } catch (error) {
        console.error('Error fetching appointments:', error);
        appointments.value = [];
    } finally {
        loading.value = false;
    }
};

const fetchDepartments = async () => {
    try {
        const response = await axios.get('/api/departments');
        const responseData = response.data.data || response.data;
        departments.value = Array.isArray(responseData) ? responseData : [];
    } catch (error) {
        console.error('Error fetching departments:', error);
    }
};

// Helper function to format date without timezone conversion
const formatDateToYMD = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getAppointmentsForDate = (dateStr) => {
    return appointments.value.filter(appt => {
        const apptDate = appt.appointment_date?.split('T')[0] || appt.appointment_date;
        return apptDate === dateStr;
    });
};

const getAppointmentsForHour = (hourStr) => {
    const hour = parseInt(hourStr.split(':')[0]);
    const isPM = hourStr.includes('PM');
    const hour24 = isPM && hour !== 12 ? hour + 12 : hour === 12 && !isPM ? 0 : hour;

    return dayAppointments.value.filter(appt => {
        if (!appt.appointment_time) return false;
        const apptHour = parseInt(appt.appointment_time.split(':')[0]);
        return apptHour === hour24;
    });
};

const getAppointmentStyle = (appt) => {
    if (!appt.appointment_time) return {};
    const [hours, minutes] = appt.appointment_time.split(':');
    const hour = parseInt(hours);
    const minute = parseInt(minutes);
    const top = ((hour - 8) * 60 + minute) * (60 / 60); // 60px per hour
    return {
        top: `${top}px`,
        height: '58px' // Default 1-hour slot minus margin
    };
};

const selectDate = (day) => {
    currentDate.value = new Date(day.date);
    view.value = 'day';
};

const viewAppointment = (appt) => {
    selectedAppointment.value = appt;
    if (!modal) {
        modal = new Modal(modalRef.value);
    }
    modal.show();
};

const today = () => {
    currentDate.value = new Date();
    fetchAppointments();
};

const previousPeriod = () => {
    const date = new Date(currentDate.value);
    if (view.value === 'month') {
        date.setMonth(date.getMonth() - 1);
    } else if (view.value === 'week') {
        date.setDate(date.getDate() - 7);
    } else {
        date.setDate(date.getDate() - 1);
    }
    currentDate.value = date;
    fetchAppointments();
};

const nextPeriod = () => {
    const date = new Date(currentDate.value);
    if (view.value === 'month') {
        date.setMonth(date.getMonth() + 1);
    } else if (view.value === 'week') {
        date.setDate(date.getDate() + 7);
    } else {
        date.setDate(date.getDate() + 1);
    }
    currentDate.value = date;
    fetchAppointments();
};

const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getStatusColor = (status) => {
    const colors = {
        completed: 'success',
        confirmed: 'primary',
        checked_in: 'info',
        in_consultation: 'info',
        scheduled: 'warning',
        cancelled: 'danger',
        no_show: 'secondary'
    };
    return colors[status] || 'secondary';
};

// Watchers
import { watch } from 'vue';
watch(view, () => {
    fetchAppointments();
});

onMounted(() => {
    fetchDepartments();
    fetchAppointments();
});
</script>

<style scoped>
/* Month View */
.calendar-month {
    padding: 1rem;
}

.calendar-weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.calendar-weekday {
    text-align: center;
    font-weight: 600;
    padding: 0.5rem;
    color: #6c757d;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
}

.calendar-day {
    min-height: 100px;
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.calendar-day:hover {
    background-color: #f8f9fa;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    opacity: 0.5;
}

.calendar-day.today {
    background-color: rgba(54, 153, 255, 0.1);
    border-color: #3699ff;
}

.calendar-day.today .day-number {
    background-color: #3699ff;
    color: white;
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.day-number {
    font-weight: 600;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.day-appointments {
    font-size: 0.75rem;
}

.appointment-badge {
    padding: 0.125rem 0.25rem;
    margin-bottom: 0.125rem;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: transform 0.15s ease;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.appointment-badge:hover {
    transform: translateX(2px);
}

.appointment-badge.status-completed {
    background-color: #d4edda;
    border-left: 3px solid #28a745;
}

.appointment-badge.status-confirmed {
    background-color: #cfe2ff;
    border-left: 3px solid #0d6efd;
}

.appointment-badge.status-checked_in,
.appointment-badge.status-in_consultation {
    background-color: #d1ecf1;
    border-left: 3px solid #0dcaf0;
}

.appointment-badge.status-scheduled {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}

.appointment-badge.status-cancelled {
    background-color: #f8d7da;
    border-left: 3px solid #dc3545;
}

.more-badge {
    text-align: center;
    color: #6c757d;
    font-weight: 600;
    margin-top: 0.25rem;
}

/* Week View */
.calendar-week {
    display: flex;
    min-height: 600px;
}

.time-column {
    width: 80px;
    border-right: 1px solid #dee2e6;
}

.time-slot-header {
    height: 80px;
    border-bottom: 1px solid #dee2e6;
}

.time-slot {
    height: 60px;
    padding: 0.5rem;
    border-bottom: 1px solid #f1f3f5;
    font-size: 0.75rem;
    color: #6c757d;
}

.day-column {
    flex: 1;
    border-right: 1px solid #dee2e6;
    position: relative;
}

.day-column:last-child {
    border-right: none;
}

.day-header {
    height: 80px;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    text-align: center;
}

.day-header.today {
    background-color: rgba(54, 153, 255, 0.1);
}

.day-name {
    font-weight: 600;
    font-size: 0.875rem;
    color: #6c757d;
}

.day-date {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0.25rem 0;
}

.appointment-count {
    font-size: 0.75rem;
    color: #6c757d;
}

.day-timeline {
    position: relative;
    height: 660px;
}

.timeline-appointment {
    position: absolute;
    left: 4px;
    right: 4px;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
    font-size: 0.75rem;
    overflow: hidden;
    transition: transform 0.15s ease;
}

.timeline-appointment:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

.timeline-appointment.status-completed {
    background-color: #d4edda;
    border-left: 3px solid #28a745;
}

.timeline-appointment.status-confirmed {
    background-color: #cfe2ff;
    border-left: 3px solid #0d6efd;
}

.timeline-appointment.status-checked_in,
.timeline-appointment.status-in_consultation {
    background-color: #d1ecf1;
    border-left: 3px solid #0dcaf0;
}

.timeline-appointment.status-scheduled {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}

.timeline-appointment.status-cancelled {
    background-color: #f8d7da;
    border-left: 3px solid #dc3545;
}

.appointment-time,
.appointment-patient,
.appointment-doctor {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.appointment-time {
    font-weight: 600;
}

/* Day View */
.day-view-timeline {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.time-row {
    display: flex;
    border-bottom: 1px solid #f1f3f5;
    min-height: 80px;
}

.time-row:last-child {
    border-bottom: none;
}

.time-label {
    width: 100px;
    padding: 0.75rem;
    border-right: 1px solid #dee2e6;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
}

.time-content {
    flex: 1;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.day-appointment {
    padding: 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: transform 0.15s ease;
}

.day-appointment:hover {
    transform: translateX(4px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

.day-appointment.status-completed {
    background-color: #d4edda;
    border-left: 4px solid #28a745;
}

.day-appointment.status-confirmed {
    background-color: #cfe2ff;
    border-left: 4px solid #0d6efd;
}

.day-appointment.status-checked_in,
.day-appointment.status-in_consultation {
    background-color: #d1ecf1;
    border-left: 4px solid #0dcaf0;
}

.day-appointment.status-scheduled {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.day-appointment.status-cancelled {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}

/* Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 0.375rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
</style>
