<template>
    <div class="container-fluid py-3">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Bed Transfer Management</h5>
                <button class="btn btn-primary" @click="openTransferModal">
                    <i class="bi bi-arrow-left-right"></i> New Transfer
                </button>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" v-model="search" placeholder="Search by patient name, IPD number...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" v-model="filterType">
                            <option value="">All Types</option>
                            <option value="transfer">Transfer</option>
                            <option value="swap">Swap</option>
                            <option value="move">Move</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" v-model="filterDate">
                    </div>
                </div>

                <!-- Transfer List -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>Type</th>
                                <th>Patient</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Reason</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-3">Loading...</td>
                            </tr>
                            <tr v-else-if="filteredTransfers.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">No bed transfers found</td>
                            </tr>
                            <tr v-for="transfer in filteredTransfers" :key="transfer.transfer_id">
                                <td>
                                    {{ formatDate(transfer.transfer_datetime) }}
                                    <small class="text-muted d-block">{{ formatTime(transfer.transfer_datetime) }}</small>
                                </td>
                                <td>
                                    <span class="badge" :class="getTypeBadge(transfer.transfer_type, transfer.move_completion_type)">
                                        {{ transfer.display_type }}
                                    </span>
                                    <span v-if="transfer.transfer_type === 'move' && !transfer.is_move_completed" class="badge bg-warning ms-1" title="Move not yet completed">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ transfer.patient_name }}</strong>
                                    <small class="text-muted d-block">{{ transfer.ipd_number }}</small>
                                </td>
                                <td>
                                    {{ transfer.from_ward_name }} - {{ transfer.from_bed_number }}
                                </td>
                                <td>
                                    {{ transfer.to_ward_name }} - {{ transfer.to_bed_number }}
                                </td>
                                <td>{{ transfer.reason || '-' }}</td>
                                <td>{{ transfer.transferred_by_name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <div class="modal fade" :class="{ show: showModal }" :style="{ display: showModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bed Transfer</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Active Move Alert -->
                        <div v-if="activeMove" class="alert alert-warning mb-4">
                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Active Move Detected</h6>
                            <p class="mb-2">This patient has an active temporary move:</p>
                            <ul class="mb-2">
                                <li><strong>Origin Bed:</strong> {{ activeMove.from_ward_name }} - Bed {{ activeMove.from_bed_number }}</li>
                                <li><strong>Current Bed:</strong> {{ activeMove.current_ward_name }} - Bed {{ activeMove.current_bed_number }}</li>
                            </ul>
                            <p class="mb-0">Please complete this move before starting a new transfer.</p>
                        </div>

                        <!-- Transfer Type Selection -->
                        <div class="mb-4">
                            <label class="form-label">Transfer Type *</label>
                            <div v-if="activeMove" class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" id="type-back-origin" value="back_to_origin" v-model="form.transfer_type">
                                <label class="btn btn-outline-success" for="type-back-origin">
                                    <i class="bi bi-arrow-left"></i> Back to Origin
                                    <small class="d-block">Return to original bed</small>
                                </label>

                                <input type="radio" class="btn-check" id="type-new-bed" value="new_bed" v-model="form.transfer_type">
                                <label class="btn btn-outline-primary" for="type-new-bed">
                                    <i class="bi bi-arrow-right"></i> Move to New Bed
                                    <small class="d-block">Free both beds, move to new one</small>
                                </label>
                            </div>
                            <div v-else class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" id="type-transfer" value="transfer" v-model="form.transfer_type">
                                <label class="btn btn-outline-primary" for="type-transfer">
                                    <i class="bi bi-arrow-right"></i> Transfer
                                    <small class="d-block">Empty old bed, occupy new bed</small>
                                </label>

                                <input type="radio" class="btn-check" id="type-swap" value="swap" v-model="form.transfer_type">
                                <label class="btn btn-outline-warning" for="type-swap">
                                    <i class="bi bi-arrow-left-right"></i> Swap
                                    <small class="d-block">Exchange beds between 2 patients</small>
                                </label>

                                <input type="radio" class="btn-check" id="type-move" value="move" v-model="form.transfer_type">
                                <label class="btn btn-outline-info" for="type-move">
                                    <i class="bi bi-plus-circle"></i> Move
                                    <small class="d-block">Occupy new bed, keep old bed</small>
                                </label>
                            </div>
                        </div>

                        <!-- Patient Selection -->
                        <div class="mb-3">
                            <label class="form-label">Patient (IPD Admission) *</label>
                            <select class="form-select" v-model="form.ipd_id" @change="onPatientChange">
                                <option value="">-- Select Patient --</option>
                                <option v-for="admission in admittedPatients" :key="admission.ipd_id" :value="admission.ipd_id">
                                    {{ admission.patient_name }} - {{ admission.ipd_number }} - {{ admission.ward_name }} / {{ admission.bed_number }}
                                </option>
                            </select>
                        </div>

                        <!-- Current Bed Info -->
                        <div v-if="currentBedInfo" class="alert alert-info">
                            <strong>Current Location:</strong> {{ currentBedInfo.ward_name }} - Bed {{ currentBedInfo.bed_number }}
                        </div>

                        <!-- Swap Patient Selection (only for swap type) -->
                        <div v-if="form.transfer_type === 'swap'" class="mb-3">
                            <label class="form-label">Swap With Patient *</label>
                            <select class="form-select" v-model="form.swap_ipd_id">
                                <option value="">-- Select Patient to Swap With --</option>
                                <option v-for="admission in admittedPatients.filter(a => a.ipd_id != form.ipd_id)" :key="admission.ipd_id" :value="admission.ipd_id">
                                    {{ admission.patient_name }} - {{ admission.ipd_number }} - {{ admission.ward_name }} / {{ admission.bed_number }}
                                </option>
                            </select>
                        </div>

                        <!-- Back to Origin Info -->
                        <div v-if="form.transfer_type === 'back_to_origin' && activeMove" class="alert alert-success mb-3">
                            <strong><i class="bi bi-arrow-left"></i> Returning to Origin Bed:</strong><br>
                            {{ activeMove.from_ward_name }} - Bed {{ activeMove.from_bed_number }}
                        </div>

                        <!-- New Bed Selection (for transfer, move, and new_bed) -->
                        <div v-if="form.transfer_type === 'transfer' || form.transfer_type === 'move' || form.transfer_type === 'new_bed'" class="mb-3">
                            <label class="form-label">New Bed *</label>
                            <select class="form-select" v-model="form.to_bed_id">
                                <option value="">-- Select New Bed --</option>
                                <optgroup v-for="room in availableBedsByRoom" :key="room.room_id" :label="room.room_name + ' (' + room.ward_name + ')'">
                                    <option v-for="bed in room.beds" :key="bed.bed_id" :value="bed.bed_id">
                                        Bed {{ bed.bed_number }} - {{ bed.bed_type }} (Rs {{ bed.charges_per_day }}/day)
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label class="form-label">Reason for Transfer *</label>
                            <textarea class="form-control" v-model="form.reason" rows="3" placeholder="Enter reason for bed transfer..."></textarea>
                        </div>

                        <!-- Transfer Date/Time -->
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Transfer Date *</label>
                                <input type="date" class="form-control" v-model="form.transfer_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transfer Time *</label>
                                <input type="time" class="form-control" v-model="form.transfer_time">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="submitTransfer" :disabled="!canSubmit">
                            <i class="bi bi-check-lg"></i> Confirm Transfer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'BedTransferMaster',
    setup() {
        const loading = ref(false);
        const transfers = ref([]);
        const admittedPatients = ref([]);
        const availableBeds = ref([]);
        const showModal = ref(false);
        const search = ref('');
        const filterType = ref('');
        const filterDate = ref('');

        const form = ref({
            transfer_type: 'transfer',
            ipd_id: '',
            swap_ipd_id: '',
            to_bed_id: '',
            parent_move_transfer_id: '',
            reason: '',
            transfer_date: new Date().toISOString().split('T')[0],
            transfer_time: new Date().toTimeString().split(' ')[0].substring(0, 5),
        });

        const currentBedInfo = ref(null);
        const activeMove = ref(null);

        const filteredTransfers = computed(() => {
            let result = transfers.value;

            if (search.value) {
                const searchLower = search.value.toLowerCase();
                result = result.filter(t =>
                    t.patient_name.toLowerCase().includes(searchLower) ||
                    t.ipd_number.toLowerCase().includes(searchLower)
                );
            }

            if (filterType.value) {
                result = result.filter(t => t.transfer_type === filterType.value);
            }

            if (filterDate.value) {
                result = result.filter(t => t.transfer_datetime.startsWith(filterDate.value));
            }

            return result;
        });

        const availableBedsByRoom = computed(() => {
            const roomsMap = new Map();

            availableBeds.value.forEach(bed => {
                if (bed.room && bed.status === 'available') {
                    if (!roomsMap.has(bed.room.room_id)) {
                        roomsMap.set(bed.room.room_id, {
                            room_id: bed.room.room_id,
                            room_name: bed.room.room_name,
                            ward_name: bed.room.ward ? bed.room.ward.ward_name : '',
                            beds: []
                        });
                    }
                    roomsMap.get(bed.room.room_id).beds.push(bed);
                }
            });

            return Array.from(roomsMap.values());
        });

        const canSubmit = computed(() => {
            if (!form.value.ipd_id || !form.value.reason || !form.value.transfer_date || !form.value.transfer_time) {
                return false;
            }

            if (form.value.transfer_type === 'swap' && !form.value.swap_ipd_id) {
                return false;
            }

            if ((form.value.transfer_type === 'transfer' || form.value.transfer_type === 'move' || form.value.transfer_type === 'new_bed') && !form.value.to_bed_id) {
                return false;
            }

            // back_to_origin doesn't need to_bed_id as it goes back to origin

            return true;
        });

        const loadTransfers = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/bed-transfers');
                transfers.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load transfers:', error);
            } finally {
                loading.value = false;
            }
        };

        const loadAdmittedPatients = async () => {
            try {
                const response = await axios.get('/api/ipd-admissions', {
                    params: { status: 'admitted', minimal: 'true' }
                });
                admittedPatients.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load admitted patients:', error);
            }
        };

        const loadAvailableBeds = async () => {
            try {
                const response = await axios.get('/api/beds', {
                    params: { status: 'available', include_room: 1 }
                });
                availableBeds.value = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to load available beds:', error);
            }
        };

        const openTransferModal = () => {
            showModal.value = true;
            loadAdmittedPatients();
            loadAvailableBeds();
        };

        const closeModal = () => {
            showModal.value = false;
            currentBedInfo.value = null;
            activeMove.value = null;
            form.value = {
                transfer_type: 'transfer',
                ipd_id: '',
                swap_ipd_id: '',
                to_bed_id: '',
                parent_move_transfer_id: '',
                reason: '',
                transfer_date: new Date().toISOString().split('T')[0],
                transfer_time: new Date().toTimeString().split(' ')[0].substring(0, 5),
            };
        };

        const checkActiveMove = async (ipdId) => {
            try {
                const response = await axios.get('/api/bed-transfers/active-move', {
                    params: { ipd_id: ipdId }
                });
                if (response.data.has_active_move) {
                    activeMove.value = response.data.active_move;
                    form.value.parent_move_transfer_id = response.data.active_move.transfer_id;
                    form.value.transfer_type = 'back_to_origin'; // Default to back to origin
                } else {
                    activeMove.value = null;
                    form.value.parent_move_transfer_id = '';
                    form.value.transfer_type = 'transfer'; // Default to transfer
                }
            } catch (error) {
                console.error('Failed to check active move:', error);
                activeMove.value = null;
            }
        };

        const onPatientChange = async () => {
            const selectedPatient = admittedPatients.value.find(p => p.ipd_id == form.value.ipd_id);
            if (selectedPatient) {
                currentBedInfo.value = {
                    ward_name: selectedPatient.ward_name,
                    bed_number: selectedPatient.bed_number,
                };
                // Check for active move
                await checkActiveMove(form.value.ipd_id);
            }
        };

        const submitTransfer = async () => {
            try {
                await axios.post('/api/bed-transfers', form.value);
                alert('Bed transfer completed successfully');
                closeModal();
                loadTransfers();
            } catch (error) {
                alert('Failed to complete bed transfer: ' + (error.response?.data?.message || error.message));
            }
        };

        const formatDate = (datetime) => {
            if (!datetime) return '';
            const date = new Date(datetime);
            return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const formatTime = (datetime) => {
            if (!datetime) return '';
            const date = new Date(datetime);
            return date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        };

        const getTypeBadge = (type, completionType) => {
            if (completionType === 'back_to_origin') {
                return 'bg-success';
            }
            if (completionType === 'new_bed') {
                return 'bg-primary';
            }
            const badges = {
                transfer: 'bg-primary',
                swap: 'bg-warning',
                move: 'bg-info'
            };
            return badges[type] || 'bg-secondary';
        };

        onMounted(() => {
            loadTransfers();
        });

        return {
            loading,
            transfers,
            admittedPatients,
            availableBeds,
            showModal,
            search,
            filterType,
            filterDate,
            form,
            currentBedInfo,
            activeMove,
            filteredTransfers,
            availableBedsByRoom,
            canSubmit,
            openTransferModal,
            closeModal,
            onPatientChange,
            submitTransfer,
            formatDate,
            formatTime,
            getTypeBadge,
        };
    }
};
</script>

<style scoped>
.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    color: white;
}

.modal.show {
    display: block;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: #000;
    opacity: 0.5;
}
</style>
