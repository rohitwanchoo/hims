<template>
  <div class="doctor-workbench">
    <div class="container-fluid py-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
          <i class="bi bi-clipboard2-pulse me-2"></i>
          Doctor Workbench
        </h2>
        <div class="d-flex gap-2">
          <!-- Date Filter -->
          <input
            type="date"
            v-model="selectedDate"
            class="form-control"
            @change="fetchData"
          />
          <!-- Doctor Filter (Admin only) -->
          <select
            v-if="isAdmin"
            v-model="selectedDoctorId"
            @change="fetchData"
            class="form-select"
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
          <!-- Current Filter Badge -->
          <div v-if="isAdmin" class="badge bg-primary fs-6 px-3 py-2">
            {{ selectedDoctorId ? 'Filtered by Doctor' : 'Viewing All Visits' }}
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card border-primary">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1 small">Total Visits</p>
                  <h3 class="mb-0">{{ stats.total_visits }}</h3>
                </div>
                <div class="bg-primary bg-opacity-10 p-3 rounded">
                  <i class="bi bi-people-fill text-primary fs-4"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card border-warning">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1 small">Waiting</p>
                  <h3 class="mb-0">{{ stats.waiting }}</h3>
                </div>
                <div class="bg-warning bg-opacity-10 p-3 rounded">
                  <i class="bi bi-hourglass-split text-warning fs-4"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card border-info">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1 small">In Consultation</p>
                  <h3 class="mb-0">{{ stats.in_consultation }}</h3>
                </div>
                <div class="bg-info bg-opacity-10 p-3 rounded">
                  <i class="bi bi-clipboard2-pulse text-info fs-4"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <div class="card border-success">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <p class="text-muted mb-1 small">Completed</p>
                  <h3 class="mb-0">{{ stats.completed }}</h3>
                </div>
                <div class="bg-success bg-opacity-10 p-3 rounded">
                  <i class="bi bi-check-circle-fill text-success fs-4"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Tabs -->
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
          <a
            class="nav-link"
            :class="{ active: activeTab === 'queue' }"
            @click="activeTab = 'queue'"
            href="#"
            role="tab"
          >
            <i class="bi bi-list-task me-1"></i>
            Today's Queue
          </a>
        </li>
        <li class="nav-item">
          <a
            class="nav-link"
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
          <div class="card">
            <div class="card-header bg-white">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  <i class="bi bi-calendar-check me-2"></i>
                  OPD Visits - {{ formatDate(selectedDate) }}
                </h5>
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
            <div class="card-body p-0">
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
                <table class="table table-hover mb-0">
                  <thead class="table-light">
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
          <div class="card">
            <div class="card-header bg-white">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                  <i class="bi bi-person-lines-fill me-2"></i>
                  {{ isDoctor ? 'My Assigned Patients' : 'All Patients' }}
                </h5>
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
            <div class="card-body p-0">
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
                <table class="table table-hover mb-0">
                  <thead class="table-light">
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
      alert(response.data.message || 'Consultation started successfully');
      fetchData(); // Refresh data

      // Navigate to consultation form (update route as needed)
      // router.push(`/opd/consultation/${visit.opd_id}`);
    }
  } catch (error) {
    console.error('Error starting consultation:', error);
    alert(error.response?.data?.error || 'Failed to start consultation');
  }
};

const viewConsultation = (visit) => {
  // Navigate to consultation form
  router.push(`/opd/consultation/${visit.opd_id}`);
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
}

.nav-tabs .nav-link {
  color: #6c757d;
  border: none;
  border-bottom: 2px solid transparent;
  cursor: pointer;
}

.nav-tabs .nav-link:hover {
  border-color: #dee2e6;
  color: #495057;
}

.nav-tabs .nav-link.active {
  color: #0d6efd;
  border-bottom-color: #0d6efd;
  background: none;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table > :not(caption) > * > * {
  padding: 0.75rem;
}

.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}
</style>
