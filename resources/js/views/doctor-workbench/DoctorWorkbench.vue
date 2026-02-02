<template>
  <div class="doctor-workbench">
    <div class="container-fluid py-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1 fw-bold">Doctor Workbench Dashboard</h2>
          <p class="text-muted mb-0 small">Manage patient consultations and appointments</p>
        </div>
        <div class="d-flex gap-2">
          <!-- Date Filter -->
          <input
            type="date"
            v-model="selectedDate"
            class="modern-input"
            @change="fetchData"
            style="width: auto;"
          />
          <!-- Doctor Filter (Admin only) -->
          <select
            v-if="isAdmin"
            v-model="selectedDoctorId"
            @change="fetchData"
            class="modern-select"
            style="min-width: 250px"
          >
            <option value="">🏥 All Doctors (Admin View)</option>
            <option
              v-for="doctor in doctors"
              :key="doctor.doctor_id"
              :value="doctor.doctor_id"
            >
              {{ doctor.full_name }} - {{ doctor.department?.department_name || 'N/A' }}
            </option>
          </select>
          <!-- Refresh Button -->
          <button class="modern-btn modern-btn-outline" @click="fetchData">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="stat-card stat-card-gradient-primary">
            <div class="stat-content-full">
              <div class="stat-label-top">Total Visits</div>
              <div class="stat-value-large">{{ stats.total_visits }}</div>
              <div class="stat-description">Today's appointments</div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="stat-card stat-card-gradient-warning">
            <div class="stat-content-full">
              <div class="stat-label-top">Waiting</div>
              <div class="stat-value-large">{{ stats.waiting }}</div>
              <div class="stat-description">In queue for consultation</div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="stat-card stat-card-gradient-info">
            <div class="stat-content-full">
              <div class="stat-label-top">In Consultation</div>
              <div class="stat-value-large">{{ stats.in_consultation }}</div>
              <div class="stat-description">Currently consulting</div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
          <div class="stat-card stat-card-gradient-success">
            <div class="stat-content-full">
              <div class="stat-label-top">Completed</div>
              <div class="stat-value-large">{{ stats.completed }}</div>
              <div class="stat-description">Finished consultations</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Tabs -->
      <ul class="modern-tabs mb-3" role="tablist">
        <li class="modern-tab-item">
          <a
            class="modern-tab-link"
            :class="{ active: activeTab === 'queue' }"
            @click="activeTab = 'queue'"
            href="#"
            role="tab"
          >
            <i class="bi bi-list-task me-1"></i>
            Today's Queue
          </a>
        </li>
        <li class="modern-tab-item">
          <a
            class="modern-tab-link"
            :class="{ active: activeTab === 'patients' }"
            @click="activeTab = 'patients'"
            href="#"
            role="tab"
          >
            <i class="bi bi-person-lines-fill me-1"></i>
            My Patients
          </a>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Queue Tab -->
        <div v-show="activeTab === 'queue'">
          <div class="modern-card">
            <div class="modern-card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                  <i class="bi bi-calendar-check me-2"></i>
                  OPD Visits - {{ formatDate(selectedDate) }}
                </h6>
                <div v-if="isAdmin && !selectedDoctorId" class="badge bg-info">
                  <i class="bi bi-info-circle me-1"></i>
                  Showing all doctors' patients
                </div>
                <div v-else-if="isDoctor && currentDoctor" class="badge bg-success">
                  <i class="bi bi-person-badge me-1"></i>
                  Dr. {{ currentDoctor.full_name }}
                </div>
              </div>
            </div>
            <div class="modern-card-body p-0">
              <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="visits.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No OPD visits for selected date</p>
              </div>
              <div v-else class="table-responsive">
                <table class="table table-hover mb-0 modern-table">
                  <thead>
                    <tr>
                      <th>Token</th>
                      <th>Patient</th>
                      <th>Age/Gender</th>
                      <th>Mobile</th>
                      <th>Doctor</th>
                      <th>Department</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="visit in visits" :key="visit.opd_id">
                      <td>
                        <span class="badge bg-primary">{{ visit.token_number }}</span>
                      </td>
                      <td>
                        <strong>{{ visit.patient?.patient_name || 'N/A' }}</strong>
                        <br />
                        <small class="text-muted">{{ visit.patient?.pcd }}</small>
                      </td>
                      <td>
                        {{ visit.patient?.age_years || visit.patient?.age || 'N/A' }} /
                        {{ visit.patient?.gender_relation?.gender_name || visit.patient?.gender || 'N/A' }}
                      </td>
                      <td>{{ visit.patient?.mobile_number || 'N/A' }}</td>
                      <td>{{ visit.doctor?.full_name || 'N/A' }}</td>
                      <td>{{ visit.department?.department_name || 'N/A' }}</td>
                      <td>
                        <span
                          class="badge"
                          :class="{
                            'bg-warning': visit.status === 'waiting',
                            'bg-info': visit.status === 'in_consultation',
                            'bg-success': visit.status === 'completed'
                          }"
                        >
                          {{ formatStatus(visit.status) }}
                        </span>
                      </td>
                      <td>
                        <button
                          v-if="visit.status === 'waiting'"
                          @click="startConsultation(visit)"
                          class="btn btn-sm btn-primary me-1"
                        >
                          <i class="bi bi-play-circle"></i> Start
                        </button>
                        <button
                          v-if="visit.status === 'in_consultation'"
                          @click="viewConsultation(visit)"
                          class="btn btn-sm btn-info me-1"
                        >
                          <i class="bi bi-eye"></i> Continue
                        </button>
                        <button
                          @click="viewPatientHistory(visit.patient)"
                          class="btn btn-sm btn-outline-secondary"
                          title="View History"
                        >
                          <i class="bi bi-clock-history"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Patients Tab -->
        <div v-show="activeTab === 'patients'">
          <div class="modern-card">
            <div class="modern-card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                  <i class="bi bi-person-lines-fill me-2"></i>
                  {{ isDoctor ? 'My Assigned Patients' : 'All Patients' }}
                </h6>
                <div class="d-flex align-items-center gap-3">
                  <div v-if="isAdmin" class="badge bg-info">
                    <i class="bi bi-eye me-1"></i>
                    Admin View
                  </div>
                  <div class="text-muted">
                    Total: {{ patients.length }} patients
                  </div>
                </div>
              </div>
            </div>
            <div class="modern-card-body p-0">
              <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="patients.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-2">No patients found</p>
              </div>
              <div v-else class="table-responsive">
                <table class="table table-hover mb-0 modern-table">
                  <thead>
                    <tr>
                      <th>Patient ID</th>
                      <th>Patient Name</th>
                      <th>Age/Gender</th>
                      <th>Mobile</th>
                      <th>Blood Group</th>
                      <th>Last Visit</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="patient in patients" :key="patient.patient_id">
                      <td>
                        <strong>{{ patient.pcd }}</strong>
                      </td>
                      <td>{{ patient.patient_name || 'N/A' }}</td>
                      <td>
                        {{ patient.age_years || patient.age || 'N/A' }} /
                        {{ patient.gender_relation?.gender_name || patient.gender || 'N/A' }}
                      </td>
                      <td>{{ patient.mobile_number || 'N/A' }}</td>
                      <td>
                        {{ patient.blood_group_relation?.blood_group_name || patient.blood_group || 'N/A' }}
                      </td>
                      <td>
                        <span v-if="patient.latest_opd_visit">
                          {{ formatDate(patient.latest_opd_visit.visit_date) }}
                          <br />
                          <small class="text-muted">
                            Dr. {{ patient.latest_opd_visit.doctor?.full_name }}
                          </small>
                        </span>
                        <span v-else class="text-muted">No visits</span>
                      </td>
                      <td>
                        <button
                          @click="editPatientConsultation(patient)"
                          class="btn btn-sm btn-primary me-1"
                          title="Edit Consultation"
                        >
                          <i class="bi bi-pencil-square"></i> Edit
                        </button>
                        <button
                          @click="viewPatientHistory(patient)"
                          class="btn btn-sm btn-outline-primary"
                        >
                          <i class="bi bi-clock-history"></i> History
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Patient History Modal -->
    <div
      class="modal fade"
      id="patientHistoryModal"
      tabindex="-1"
      ref="historyModal"
    >
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-person-badge me-2"></i>
              Patient History
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
            ></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedPatient">
              <!-- Patient Info -->
              <div class="card mb-3">
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-3">
                      <strong>Patient ID:</strong>
                      <p>{{ selectedPatient.pcd }}</p>
                    </div>
                    <div class="col-md-3">
                      <strong>Name:</strong>
                      <p>{{ selectedPatient.patient_name }}</p>
                    </div>
                    <div class="col-md-3">
                      <strong>Age/Gender:</strong>
                      <p>
                        {{ selectedPatient.age_years || selectedPatient.age || 'N/A' }} /
                        {{ selectedPatient.gender_relation?.gender_name || selectedPatient.gender || 'N/A' }}
                      </p>
                    </div>
                    <div class="col-md-3">
                      <strong>Blood Group:</strong>
                      <p>
                        {{ selectedPatient.blood_group_relation?.blood_group_name || selectedPatient.blood_group || 'N/A' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- OPD Visits History -->
              <h6 class="mb-3">
                <i class="bi bi-clipboard2-pulse me-2"></i>
                OPD Visit History
              </h6>
              <div v-if="loadingHistory" class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              <div v-else-if="patientOpdVisits.length === 0" class="alert alert-info">
                No OPD visits found for this patient
              </div>
              <div v-else class="table-responsive">
                <table class="table table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Visit Date</th>
                      <th>Token</th>
                      <th>Doctor</th>
                      <th>Department</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="visit in patientOpdVisits" :key="visit.opd_id">
                      <td>{{ formatDate(visit.visit_date) }}</td>
                      <td>{{ visit.token_number }}</td>
                      <td>{{ visit.doctor?.full_name || 'N/A' }}</td>
                      <td>{{ visit.department?.department_name || 'N/A' }}</td>
                      <td>
                        <span
                          class="badge"
                          :class="{
                            'bg-warning': visit.status === 'waiting',
                            'bg-info': visit.status === 'in_consultation',
                            'bg-success': visit.status === 'completed'
                          }"
                        >
                          {{ formatStatus(visit.status) }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              data-bs-dismiss="modal"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Modal } from 'bootstrap';

const router = useRouter();

// State
const loading = ref(false);
const loadingHistory = ref(false);
const activeTab = ref('queue');
const selectedDate = ref(new Date().toISOString().split('T')[0]);
const selectedDoctorId = ref('');

// Data
const visits = ref([]);
const patients = ref([]);
const doctors = ref([]);
const stats = ref({
  total_visits: 0,
  waiting: 0,
  in_consultation: 0,
  completed: 0,
  total_patients: 0
});

// User info
const currentDoctor = ref(null);
const isDoctor = ref(false);
const isAdmin = ref(false);

// Patient history
const selectedPatient = ref(null);
const patientOpdVisits = ref([]);
const historyModal = ref(null);
let historyModalInstance = null;

// Methods
const fetchData = async () => {
  loading.value = true;
  try {
    const params = {
      date: selectedDate.value
    };

    if (selectedDoctorId.value) {
      params.doctor_id = selectedDoctorId.value;
    }

    console.log('Fetching workbench data with params:', params);
    const response = await axios.get('/api/doctor-workbench', { params });

    console.log('API Response:', response.data);

    if (response.data.success) {
      visits.value = response.data.data.visits || [];
      patients.value = response.data.data.patients || [];
      stats.value = response.data.data.stats || {};
      doctors.value = response.data.data.doctors || [];
      currentDoctor.value = response.data.data.current_doctor;
      isDoctor.value = response.data.data.is_doctor;
      isAdmin.value = response.data.data.is_admin;

      console.log('Workbench data loaded:', {
        visits: visits.value.length,
        patients: patients.value.length,
        isAdmin: isAdmin.value,
        isDoctor: isDoctor.value,
        currentDoctor: currentDoctor.value?.full_name,
        stats: stats.value
      });
    } else {
      console.error('API response not successful:', response.data);
    }
  } catch (error) {
    console.error('Error fetching workbench data:', error);
    console.error('Error response:', error.response?.data);
    console.error('Error status:', error.response?.status);
    alert('Failed to load workbench data: ' + (error.response?.data?.message || error.message));
  } finally {
    loading.value = false;
  }
};

const startConsultation = async (visit) => {
  if (!confirm(`Start consultation for ${visit.patient?.patient_name}?`)) {
    return;
  }

  try {
    const response = await axios.post(
      `/api/doctor-workbench/consultation/${visit.opd_id}/start`
    );

    if (response.data.success) {
      // Navigate to dynamic consultation form
      router.push(`/consultation/${visit.opd_id}?patient_id=${visit.patient_id}&form_type=opd`);
    }
  } catch (error) {
    console.error('Error starting consultation:', error);
    alert(error.response?.data?.error || 'Failed to start consultation');
  }
};

const viewConsultation = (visit) => {
  // Navigate to dynamic consultation form
  router.push(`/consultation/${visit.opd_id}?patient_id=${visit.patient_id}&form_type=opd`);
};

const editPatientConsultation = (patient) => {
  // Check if patient has a latest OPD visit
  if (patient.latest_opd_visit) {
    // Navigate to the latest consultation
    router.push(`/consultation/${patient.latest_opd_visit.opd_id}?patient_id=${patient.patient_id}&form_type=opd`);
  } else {
    // No OPD visit exists, navigate with just patient_id
    // This will load the consultation form without an opd_id
    alert('This patient has no OPD visits. Please create an OPD visit first from the OPD module.');
  }
};

const viewPatientHistory = async (patient) => {
  selectedPatient.value = patient;
  loadingHistory.value = true;

  // Show modal
  if (!historyModalInstance) {
    historyModalInstance = new Modal(historyModal.value);
  }
  historyModalInstance.show();

  try {
    const response = await axios.get(
      `/api/doctor-workbench/patient/${patient.patient_id}/history`
    );

    if (response.data.success) {
      patientOpdVisits.value = response.data.data.opd_visits || [];
    }
  } catch (error) {
    console.error('Error fetching patient history:', error);
    alert(error.response?.data?.error || 'Failed to load patient history');
  } finally {
    loadingHistory.value = false;
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const formatStatus = (status) => {
  const statusMap = {
    waiting: 'Waiting',
    in_consultation: 'In Consultation',
    completed: 'Completed'
  };
  return statusMap[status] || status;
};

// Lifecycle
onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.doctor-workbench {
  min-height: 100vh;
  background-color: #f8f9fa;
  padding: 1.5rem;
}

/* Modern Buttons */
.modern-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  border: none;
  transition: all 0.3s ease;
  cursor: pointer;
  text-decoration: none;
}

.modern-btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.modern-btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
  color: white;
}

.modern-btn-outline {
  background: white;
  color: #6c757d;
  border: 1px solid #e0e0e0;
}

.modern-btn-outline:hover {
  background: #f8f9fa;
  border-color: #667eea;
  color: #667eea;
}

/* Modern Stat Cards with Gradients */
.stat-card {
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  border: none;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;
  min-height: 130px;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

/* Gradient Backgrounds */
.stat-card-gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-card-gradient-warning {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.stat-card-gradient-info {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  color: white;
}

.stat-card-gradient-success {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  color: white;
}

.stat-content-full {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label-top {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  opacity: 0.9;
}

.stat-value-large {
  font-size: 2.25rem;
  font-weight: 700;
  line-height: 1;
  margin: 0.25rem 0;
}

.stat-description {
  font-size: 0.75rem;
  opacity: 0.85;
  line-height: 1.4;
}

/* Modern Cards */
.modern-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.modern-card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}

.modern-card-header h6 {
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
}

.modern-card-body {
  padding: 1.5rem;
}

/* Modern Tabs */
.modern-tabs {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0;
  border-bottom: 2px solid #f0f0f0;
  gap: 1rem;
}

.modern-tab-item {
  list-style: none;
}

.modern-tab-link {
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  color: #6c757d;
  text-decoration: none;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
  cursor: pointer;
  font-weight: 500;
  margin-bottom: -2px;
}

.modern-tab-link:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
  border-radius: 8px 8px 0 0;
}

.modern-tab-link.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

/* Modern Table */
.modern-table {
  font-size: 0.875rem;
}

.modern-table thead th {
  background: #fafafa;
  color: #6c757d;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 1rem 1.25rem;
  border-bottom: 2px solid #f0f0f0;
}

.modern-table tbody td {
  padding: 1rem 1.25rem;
  vertical-align: middle;
  color: #2c3e50;
}

.modern-table tbody tr {
  transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
  background: #f8f9fa;
}

/* Modern Inputs */
.modern-select,
.modern-input {
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  transition: all 0.2s ease;
  background: white;
  color: #2c3e50;
}

.modern-select:focus,
.modern-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select:hover,
.modern-input:hover {
  border-color: #b0b0b0;
}

/* Legacy Styles */
.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>
