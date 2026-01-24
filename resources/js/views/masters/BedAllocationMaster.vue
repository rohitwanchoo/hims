<template>
  <div class="bed-allocation-master">
    <div class="page-header mb-4">
      <h4>
        <i class="bi bi-hospital me-2"></i>
        Bed Allocation Master
      </h4>
      <p class="text-muted mb-0">Manage wards, rooms, and bed allocation</p>
    </div>

    <div class="row g-4">
      <!-- Left Side: Ward Management -->
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-header gradient-primary text-white">
            <h6 class="mb-0">
              <i class="bi bi-building me-2"></i>
              Ward Management
            </h6>
          </div>
          <div class="card-body">
            <!-- Ward Form -->
            <div class="mb-4">
              <div class="mb-3">
                <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                <input
                  v-model="wardForm.ward_name"
                  type="text"
                  class="form-control"
                  placeholder="Enter ward name (e.g., General Ward)"
                  :class="{ 'is-invalid': wardErrors.ward_name }"
                />
                <div v-if="wardErrors.ward_name" class="invalid-feedback">
                  {{ wardErrors.ward_name }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Ward Type</label>
                <select v-model="wardForm.ward_type" class="form-select">
                  <option value="general">General</option>
                  <option value="private">Private</option>
                  <option value="semi_private">Semi Private</option>
                  <option value="icu">ICU</option>
                  <option value="nicu">NICU</option>
                  <option value="picu">PICU</option>
                </select>
              </div>

              <div class="d-flex gap-2">
                <button
                  class="btn btn-primary flex-grow-1"
                  @click="saveWard"
                  :disabled="savingWard"
                >
                  <i class="bi bi-save me-1"></i>
                  {{ editingWardId ? 'Update' : 'Save' }} Ward
                </button>
                <button
                  v-if="editingWardId"
                  class="btn btn-secondary"
                  @click="cancelEditWard"
                >
                  <i class="bi bi-x-circle me-1"></i>
                  Cancel
                </button>
              </div>
            </div>

            <!-- Ward Table -->
            <div class="table-responsive">
              <table class="table table-hover">
                <thead class="table-light">
                  <tr>
                    <th>Ward Name</th>
                    <th>Type</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="wards.length === 0">
                    <td colspan="3" class="text-center text-muted">
                      <i class="bi bi-inbox me-2"></i>
                      No wards added yet
                    </td>
                  </tr>
                  <tr v-for="ward in wards" :key="ward.ward_id">
                    <td>
                      <strong>{{ ward.ward_name }}</strong>
                    </td>
                    <td>
                      <span class="badge bg-info">{{ formatWardType(ward.ward_type) }}</span>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <button
                          class="btn btn-outline-primary"
                          @click="editWard(ward)"
                          title="Edit Ward"
                        >
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button
                          class="btn btn-outline-danger"
                          @click="deleteWard(ward.ward_id)"
                          title="Delete Ward"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side: Room & Bed Management -->
      <div class="col-md-7">
        <div class="card shadow-sm">
          <div class="card-header gradient-success text-white">
            <h6 class="mb-0">
              <i class="bi bi-door-open me-2"></i>
              Room & Bed Management
            </h6>
          </div>
          <div class="card-body">
            <!-- Room Form -->
            <div class="mb-4">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Select Ward <span class="text-danger">*</span></label>
                  <select
                    v-model="roomForm.ward_id"
                    class="form-select"
                    :class="{ 'is-invalid': roomErrors.ward_id }"
                    @change="onWardSelect"
                  >
                    <option value="">Choose Ward...</option>
                    <option v-for="ward in wards" :key="ward.ward_id" :value="ward.ward_id">
                      {{ ward.ward_name }}
                    </option>
                  </select>
                  <div v-if="roomErrors.ward_id" class="invalid-feedback">
                    {{ roomErrors.ward_id }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Room Name <span class="text-danger">*</span></label>
                  <input
                    v-model="roomForm.room_name"
                    type="text"
                    class="form-control"
                    placeholder="e.g., GW"
                    :class="{ 'is-invalid': roomErrors.room_name }"
                  />
                  <div v-if="roomErrors.room_name" class="invalid-feedback">
                    {{ roomErrors.room_name }}
                  </div>
                </div>

                <div class="col-md-4">
                  <label class="form-label">No. of Beds <span class="text-danger">*</span></label>
                  <input
                    v-model.number="roomForm.bed_capacity"
                    type="number"
                    class="form-control"
                    placeholder="e.g., 10"
                    min="1"
                    :class="{ 'is-invalid': roomErrors.bed_capacity }"
                  />
                  <div v-if="roomErrors.bed_capacity" class="invalid-feedback">
                    {{ roomErrors.bed_capacity }}
                  </div>
                </div>
              </div>

              <div class="mt-3">
                <button
                  class="btn btn-success"
                  @click="saveRoom"
                  :disabled="savingRoom"
                >
                  <i class="bi bi-plus-circle me-1"></i>
                  Save Room & Beds
                </button>
              </div>
            </div>

            <!-- Room Structure Display -->
            <div v-if="selectedWardRooms.length > 0" class="room-structure">
              <h6 class="mb-3">
                <i class="bi bi-diagram-3 me-2"></i>
                Room & Bed Structure
              </h6>

              <div v-for="room in selectedWardRooms" :key="room.room_id" class="room-card mb-3">
                <div class="room-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <h6 class="mb-0">
                        <i class="bi bi-door-closed me-2"></i>
                        Ward: {{ getWardName(room.ward_id) }} | Room: {{ room.room_name }}
                      </h6>
                      <small class="text-muted">{{ room.beds.length }} Beds</small>
                    </div>
                    <button
                      class="btn btn-sm btn-outline-danger"
                      @click="deleteRoom(room.room_id)"
                      title="Delete Room"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>

                <div class="bed-grid">
                  <div
                    v-for="bed in room.beds"
                    :key="bed.bed_id"
                    class="bed-item"
                    :class="getBedStatusClass(bed.status || 'available')"
                    :title="`${room.room_name}${bed.bed_number} - ${formatBedStatus(bed.status || 'available')}`"
                  >
                    <div class="bed-icon-wrapper">
                      <i class="bi bi-hospital bed-icon"></i>
                    </div>
                    <div class="bed-details">
                      <div class="bed-number">{{ room.room_name }}{{ bed.bed_number }}</div>
                      <div class="bed-status-badge">
                        <span class="badge-sm" :class="getStatusBadgeClass(bed.status || 'available')">
                          {{ formatBedStatus(bed.status || 'available') }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="roomForm.ward_id" class="text-center text-muted py-4">
              <i class="bi bi-inbox fs-1"></i>
              <p class="mt-2">No rooms added for this ward yet</p>
            </div>

            <div v-else class="text-center text-muted py-4">
              <i class="bi bi-arrow-left fs-1"></i>
              <p class="mt-2">Please select a ward to view or add rooms</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

// Ward Management
const wards = ref([]);
const wardForm = reactive({
  ward_name: '',
  ward_type: 'general',
});
const wardErrors = ref({});
const savingWard = ref(false);
const editingWardId = ref(null);

// Room & Bed Management
const rooms = ref([]);
const roomForm = reactive({
  ward_id: '',
  room_name: '',
  bed_capacity: '',
});
const roomErrors = ref({});
const savingRoom = ref(false);

// Computed
const selectedWardRooms = computed(() => {
  if (!roomForm.ward_id) return [];
  return rooms.value.filter(room => room.ward_id == roomForm.ward_id);
});

// Ward Functions
const fetchWards = async () => {
  try {
    const response = await axios.get('/api/wards');
    wards.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching wards:', error);
  }
};

const saveWard = async () => {
  wardErrors.value = {};

  if (!wardForm.ward_name.trim()) {
    wardErrors.value.ward_name = 'Ward name is required';
    return;
  }

  savingWard.value = true;

  try {
    if (editingWardId.value) {
      // Update ward
      await axios.put(`/api/wards/${editingWardId.value}`, wardForm);
      alert('Ward updated successfully!');
    } else {
      // Create new ward
      await axios.post('/api/wards', wardForm);
      alert('Ward created successfully!');
    }

    resetWardForm();
    await fetchWards();
  } catch (error) {
    console.error('Error saving ward:', error);
    if (error.response?.data?.errors) {
      wardErrors.value = error.response.data.errors;
    } else {
      alert('Failed to save ward: ' + (error.response?.data?.message || error.message));
    }
  } finally {
    savingWard.value = false;
  }
};

const editWard = (ward) => {
  editingWardId.value = ward.ward_id;
  wardForm.ward_name = ward.ward_name;
  wardForm.ward_type = ward.ward_type;
};

const cancelEditWard = () => {
  resetWardForm();
};

const deleteWard = async (wardId) => {
  if (!confirm('Are you sure you want to delete this ward? All associated rooms and beds will be deleted.')) {
    return;
  }

  try {
    await axios.delete(`/api/wards/${wardId}`);
    alert('Ward deleted successfully!');
    await fetchWards();
    await fetchRooms();

    // Reset room form if deleted ward was selected
    if (roomForm.ward_id == wardId) {
      roomForm.ward_id = '';
    }
  } catch (error) {
    console.error('Error deleting ward:', error);
    alert('Failed to delete ward: ' + (error.response?.data?.message || error.message));
  }
};

const resetWardForm = () => {
  wardForm.ward_name = '';
  wardForm.ward_type = 'general';
  editingWardId.value = null;
  wardErrors.value = {};
};

// Room Functions
const fetchRooms = async () => {
  try {
    const response = await axios.get('/api/rooms');
    console.log('Rooms API Response:', response.data);
    rooms.value = response.data.data || response.data;
    console.log('Rooms loaded:', rooms.value.length, 'rooms');
    if (rooms.value.length > 0) {
      console.log('First room beds:', rooms.value[0].beds);
    }
  } catch (error) {
    console.error('Error fetching rooms:', error);
  }
};

const onWardSelect = () => {
  roomErrors.value = {};
};

const saveRoom = async () => {
  roomErrors.value = {};

  // Validation
  if (!roomForm.ward_id) {
    roomErrors.value.ward_id = 'Please select a ward';
    return;
  }
  if (!roomForm.room_name.trim()) {
    roomErrors.value.room_name = 'Room name is required';
    return;
  }
  if (!roomForm.bed_capacity || roomForm.bed_capacity < 1) {
    roomErrors.value.bed_capacity = 'Please enter a valid number of beds (minimum 1)';
    return;
  }

  savingRoom.value = true;

  try {
    const response = await axios.post('/api/rooms', {
      ward_id: roomForm.ward_id,
      room_name: roomForm.room_name,
      bed_capacity: roomForm.bed_capacity,
    });

    console.log('Room creation response:', response.data);
    alert(`Room created successfully with ${roomForm.bed_capacity} beds!`);
    resetRoomForm();
    await fetchRooms();
  } catch (error) {
    console.error('Error saving room:', error);
    if (error.response?.data?.errors) {
      roomErrors.value = error.response.data.errors;
    } else {
      alert('Failed to save room: ' + (error.response?.data?.message || error.message));
    }
  } finally {
    savingRoom.value = false;
  }
};

const deleteRoom = async (roomId) => {
  if (!confirm('Are you sure you want to delete this room? All associated beds will be deleted.')) {
    return;
  }

  try {
    await axios.delete(`/api/rooms/${roomId}`);
    alert('Room deleted successfully!');
    await fetchRooms();
  } catch (error) {
    console.error('Error deleting room:', error);
    alert('Failed to delete room: ' + (error.response?.data?.message || error.message));
  }
};

const resetRoomForm = () => {
  const selectedWard = roomForm.ward_id; // Keep ward selected
  roomForm.room_name = '';
  roomForm.bed_capacity = '';
  roomErrors.value = {};
};

// Helper Functions
const getWardName = (wardId) => {
  const ward = wards.value.find(w => w.ward_id == wardId);
  return ward ? ward.ward_name : 'Unknown';
};

const formatWardType = (type) => {
  return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatBedStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const getBedStatusClass = (status) => {
  return `bed-${status}`;
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'available':
      return 'bg-success text-white';
    case 'occupied':
      return 'bg-danger text-white';
    case 'maintenance':
      return 'bg-warning text-dark';
    case 'reserved':
      return 'bg-primary text-white';
    default:
      return 'bg-secondary text-white';
  }
};

// Initialize
onMounted(async () => {
  await fetchWards();
  await fetchRooms();
});
</script>

<style scoped>
.bed-allocation-master {
  padding: 1.5rem;
}

.page-header h4 {
  font-weight: 700;
  color: #2c3e50;
}

.gradient-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.gradient-success {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.card {
  border: none;
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  border: none;
  padding: 1rem 1.5rem;
}

.card-body {
  padding: 1.5rem;
}

.table {
  margin-bottom: 0;
}

.table thead th {
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #dee2e6;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

/* Room Structure */
.room-structure {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 2px dashed #dee2e6;
}

.room-card {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  border-radius: 12px;
  padding: 1.5rem;
}

.room-header {
  margin-bottom: 1.5rem;
}

.room-header h6 {
  font-weight: 600;
  color: #2c3e50;
}

/* Bed Grid */
.bed-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1.25rem;
  padding: 0.5rem;
}

.bed-item {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  text-align: center;
  transition: all 0.3s ease;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
  border: 3px solid transparent;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.bed-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  transition: height 0.3s ease;
}

.bed-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.bed-item:hover::before {
  height: 8px;
}

/* Available - Green */
.bed-available {
  border-color: #28a745;
  background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.bed-available::before {
  background: linear-gradient(90deg, #28a745, #20c997);
}

.bed-available:hover {
  border-color: #20c997;
}

/* Occupied - Red */
.bed-occupied {
  border-color: #dc3545;
  background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
}

.bed-occupied::before {
  background: linear-gradient(90deg, #dc3545, #e91e63);
}

.bed-occupied:hover {
  border-color: #e91e63;
}

/* Maintenance - Orange/Yellow */
.bed-maintenance {
  border-color: #ff9800;
  background: linear-gradient(135deg, #fff8e1 0%, #ffe082 100%);
}

.bed-maintenance::before {
  background: linear-gradient(90deg, #ff9800, #ffc107);
}

.bed-maintenance:hover {
  border-color: #ffc107;
}

/* Reserved - Blue */
.bed-reserved {
  border-color: #2196f3;
  background: linear-gradient(135deg, #e3f2fd 0%, #90caf9 100%);
}

.bed-reserved::before {
  background: linear-gradient(90deg, #2196f3, #03a9f4);
}

.bed-reserved:hover {
  border-color: #03a9f4;
}

/* Bed Icon Wrapper */
.bed-icon-wrapper {
  margin-bottom: 0.75rem;
}

.bed-icon {
  font-size: 2.5rem;
  transition: all 0.3s ease;
}

.bed-available .bed-icon {
  color: #28a745;
}

.bed-occupied .bed-icon {
  color: #dc3545;
}

.bed-maintenance .bed-icon {
  color: #ff9800;
}

.bed-reserved .bed-icon {
  color: #2196f3;
}

.bed-item:hover .bed-icon {
  transform: scale(1.1);
}

/* Bed Details */
.bed-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.bed-number {
  font-weight: 700;
  font-size: 1.2rem;
  color: #2c3e50;
  letter-spacing: 0.5px;
}

.bed-status-badge {
  display: flex;
  justify-content: center;
}

.badge-sm {
  font-size: 0.7rem;
  padding: 0.3rem 0.6rem;
  font-weight: 600;
  border-radius: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 768px) {
  .bed-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
  }

  .bed-item {
    padding: 0.75rem;
  }

  .bed-icon {
    font-size: 1.5rem;
  }

  .bed-number {
    font-size: 1rem;
  }
}
</style>
