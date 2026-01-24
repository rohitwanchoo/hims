<template>
    <div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Drug Type</label>
                <select v-model="selectedDrugType" @change="fetchDrugs" class="form-select">
                    <option value="">All Types</option>
                    <option v-for="type in drugTypes" :key="type.drug_type_id" :value="type.drug_type_id">
                        {{ type.type_name }}
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" @click="openAddDrugTypeModal">
                    <i class="bi bi-plus-lg me-1"></i> Add New Drug Type
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Drug Name *</label>
                <input type="text" class="form-control" v-model="form.drug_name" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Language</label>
                <select v-model="selectedLanguage" @change="fetchDoseTimes" class="form-select">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Dose Time</label>
                <select class="form-select" v-model="form.dose_time">
                    <option value="">Select</option>
                    <option v-for="doseTime in doseTimes" :key="doseTime.dose_time_id" :value="doseTime.dose_time_text">
                        {{ doseTime.dose_time_text }}
                    </option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">Days</label>
                <input type="number" class="form-control" v-model="form.days" min="0" />
            </div>
            <div class="col-md-1">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control" v-model="form.quantity" min="0" />
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" @click="saveDrug" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                    Save
                </button>
            </div>
        </div>

        <!-- Drug List -->
        <div class="border rounded p-3" style="height: 400px; overflow-y: auto;">
            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
            </div>
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </div>
            <div v-else-if="drugs.length === 0" class="text-center text-muted py-4">
                No drugs found
            </div>
            <div v-else>
                <div
                    v-for="drug in drugs"
                    :key="drug.drug_master_id"
                    class="drug-item py-2 px-3 mb-1 cursor-pointer d-flex justify-content-between align-items-center"
                    :class="{ 'bg-primary text-white': selectedDrug?.drug_master_id === drug.drug_master_id }"
                    @click="selectDrug(drug)"
                    tabindex="0"
                >
                    <span>{{ drug.drug_name }}</span>
                    <div class="btn-group btn-group-sm">
                        <button
                            class="btn btn-sm"
                            :class="selectedDrug?.drug_master_id === drug.drug_master_id ? 'btn-light' : 'btn-outline-primary'"
                            @click.stop="editDrug(drug)"
                            title="Edit"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button
                            class="btn btn-sm"
                            :class="selectedDrug?.drug_master_id === drug.drug_master_id ? 'btn-light text-danger' : 'btn-outline-danger'"
                            @click.stop="confirmDelete(drug)"
                            title="Delete"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Drug Type Modal -->
        <div class="modal fade" id="addDrugTypeModal" tabindex="-1" ref="addDrugTypeModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Drug Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveDrugType">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Type Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="drugTypeForm.type_name"
                                    required
                                    maxlength="100"
                                />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Drug Modal -->
        <div class="modal fade" id="editDrugModal" tabindex="-1" ref="editDrugModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Drug</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="updateDrug">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Drug Type</label>
                                <select v-model="editForm.drug_type_id" class="form-select">
                                    <option :value="null">Select Type</option>
                                    <option v-for="type in drugTypes" :key="type.drug_type_id" :value="type.drug_type_id">
                                        {{ type.type_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Drug Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="editForm.drug_name"
                                    required
                                    maxlength="255"
                                />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Language</label>
                                <select v-model="editLanguage" @change="fetchDoseTimesForEdit" class="form-select">
                                    <option value="english">English</option>
                                    <option value="marathi">Marathi</option>
                                    <option value="hindi">Hindi</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dose Time</label>
                                <select class="form-select" v-model="editForm.dose_time">
                                    <option value="">Select</option>
                                    <option v-for="doseTime in editDoseTimes" :key="doseTime.dose_time_id" :value="doseTime.dose_time_text">
                                        {{ doseTime.dose_time_text }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Days</label>
                                <input type="number" class="form-control" v-model="editForm.days" min="0" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" v-model="editForm.quantity" min="0" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const selectedDrugType = ref('');
const selectedLanguage = ref('english');
const editLanguage = ref('english');
const selectedDrug = ref(null);
const drugs = ref([]);
const drugTypes = ref([]);
const doseTimes = ref([]);
const editDoseTimes = ref([]);
const loading = ref(false);
const saving = ref(false);
const addDrugTypeModalRef = ref(null);
const editDrugModalRef = ref(null);
let addDrugTypeModal = null;
let editDrugModal = null;

const form = ref({
    drug_name: '',
    dose_time: '',
    days: '',
    quantity: ''
});

const drugTypeForm = ref({
    type_name: ''
});

const editForm = ref({
    drug_master_id: null,
    drug_type_id: null,
    drug_name: '',
    dose_time: '',
    days: '',
    quantity: ''
});

const fetchDrugTypes = async () => {
    try {
        const response = await axios.get('/api/drug-types');
        drugTypes.value = response.data;
    } catch (error) {
        console.error('Error fetching drug types:', error);
    }
};

const fetchDoseTimes = async () => {
    try {
        const response = await axios.get('/api/dose-time-masters', {
            params: { language: selectedLanguage.value }
        });
        doseTimes.value = response.data;
    } catch (error) {
        console.error('Error fetching dose times:', error);
    }
};

const fetchDoseTimesForEdit = async () => {
    try {
        const response = await axios.get('/api/dose-time-masters', {
            params: { language: editLanguage.value }
        });
        editDoseTimes.value = response.data;
    } catch (error) {
        console.error('Error fetching dose times:', error);
    }
};

const fetchDrugs = async () => {
    loading.value = true;
    try {
        const params = {};
        if (selectedDrugType.value) {
            params.drug_type_id = selectedDrugType.value;
        }
        const response = await axios.get('/api/drug-masters', { params });
        drugs.value = response.data;
    } catch (error) {
        console.error('Error fetching drugs:', error);
        alert('Error loading drugs');
    }
    loading.value = false;
};

const selectDrug = (drug) => {
    selectedDrug.value = drug;
};

const editDrug = async (drug) => {
    editForm.value = {
        drug_master_id: drug.drug_master_id,
        drug_type_id: drug.drug_type_id,
        drug_name: drug.drug_name,
        dose_time: drug.dose_time || '',
        days: drug.days || '',
        quantity: drug.quantity || ''
    };
    selectedDrug.value = drug;
    editLanguage.value = 'english';
    await fetchDoseTimesForEdit();
    editDrugModal.show();
};

const saveDrug = async () => {
    if (!form.value.drug_name.trim()) {
        alert('Please enter drug name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/drug-masters', {
            drug_type_id: selectedDrugType.value || null,
            drug_name: form.value.drug_name.trim(),
            dose_time: form.value.dose_time || null,
            days: form.value.days || null,
            quantity: form.value.quantity || null
        });

        if (response.data.success) {
            form.value = {
                drug_name: '',
                dose_time: '',
                days: '',
                quantity: ''
            };
            await fetchDrugs();
            selectedDrug.value = response.data.data;
            alert('Drug added successfully');
        }
    } catch (error) {
        console.error('Error adding drug:', error);
        alert('Error adding drug');
    }
    saving.value = false;
};

const updateDrug = async () => {
    if (!editForm.value.drug_name.trim()) {
        alert('Please enter drug name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.put(`/api/drug-masters/${editForm.value.drug_master_id}`, {
            drug_type_id: editForm.value.drug_type_id || null,
            drug_name: editForm.value.drug_name.trim(),
            dose_time: editForm.value.dose_time || null,
            days: editForm.value.days || null,
            quantity: editForm.value.quantity || null
        });

        if (response.data.success) {
            editDrugModal.hide();
            await fetchDrugs();
            alert('Drug updated successfully');
        }
    } catch (error) {
        console.error('Error updating drug:', error);
        alert('Error updating drug');
    }
    saving.value = false;
};

const confirmDelete = async (drug) => {
    if (!confirm(`Are you sure you want to delete this drug?\n\n"${drug.drug_name}"`)) {
        return;
    }

    try {
        const response = await axios.delete(`/api/drug-masters/${drug.drug_master_id}`);

        if (response.data.success) {
            if (selectedDrug.value?.drug_master_id === drug.drug_master_id) {
                selectedDrug.value = null;
            }
            await fetchDrugs();
            alert('Drug deleted successfully');
        }
    } catch (error) {
        console.error('Error deleting drug:', error);
        alert('Error deleting drug');
    }
};

const openAddDrugTypeModal = () => {
    drugTypeForm.value.type_name = '';
    addDrugTypeModal.show();
};

const saveDrugType = async () => {
    if (!drugTypeForm.value.type_name.trim()) {
        alert('Please enter drug type name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/drug-types', {
            type_name: drugTypeForm.value.type_name.trim()
        });

        if (response.data.success) {
            addDrugTypeModal.hide();
            await fetchDrugTypes();
            selectedDrugType.value = response.data.data.drug_type_id;
            await fetchDrugs();
            alert('Drug type added successfully');
        }
    } catch (error) {
        console.error('Error adding drug type:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error adding drug type');
        }
    }
    saving.value = false;
};

onMounted(() => {
    addDrugTypeModal = new Modal(addDrugTypeModalRef.value);
    editDrugModal = new Modal(editDrugModalRef.value);
    fetchDrugTypes();
    fetchDoseTimes();
    fetchDrugs();
});
</script>

<style scoped>
.drug-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.drug-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.drug-item:focus {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

.cursor-pointer {
    cursor: pointer;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
