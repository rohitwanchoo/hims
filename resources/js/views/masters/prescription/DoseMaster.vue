<template>
    <div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Language</label>
                <select v-model="selectedLanguage" @change="fetchData" class="form-select">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">Search/Add :</label>
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        v-model="searchText"
                        placeholder="Type dosage instruction"
                        @keyup.enter="addDose"
                        @input="debouncedSearch"
                    />
                    <button class="btn btn-primary" @click="addDose" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Add New
                    </button>
                </div>
            </div>
        </div>

        <!-- Dose List -->
        <div class="border rounded p-3" style="height: 450px; overflow-y: auto;">
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </div>
            <div v-else-if="doses.length === 0" class="text-center text-muted py-4">
                No dosage instructions found
            </div>
            <div v-else class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th style="width: 70%">Dosage Instruction</th>
                            <th style="width: 15%">Language</th>
                            <th style="width: 15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="dose in doses"
                            :key="dose.dose_id"
                            class="cursor-pointer"
                            :class="{ 'table-active': selectedDose?.dose_id === dose.dose_id }"
                            @click="selectDose(dose)"
                        >
                            <td>{{ dose.dose_text }}</td>
                            <td>
                                <span class="badge bg-info text-capitalize">
                                    {{ dose.language }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        @click.stop="editDose(dose)"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        class="btn btn-sm btn-outline-danger"
                                        @click.stop="confirmDelete(dose)"
                                        title="Delete"
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

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" ref="editModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Dosage</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="updateDose">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Dosage Instruction *</label>
                                <textarea
                                    class="form-control"
                                    v-model="editForm.dose_text"
                                    rows="4"
                                    required
                                    maxlength="1000"
                                ></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Language *</label>
                                <select v-model="editForm.language" class="form-select" required>
                                    <option value="english">English</option>
                                    <option value="marathi">Marathi</option>
                                    <option value="hindi">Hindi</option>
                                </select>
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

const selectedLanguage = ref('english');
const searchText = ref('');
const selectedDose = ref(null);
const doses = ref([]);
const loading = ref(false);
const saving = ref(false);
const editModalRef = ref(null);
let editModal = null;

const editForm = ref({
    dose_id: null,
    dose_text: '',
    language: 'english'
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchData();
    }, 300);
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {
            language: selectedLanguage.value
        };
        if (searchText.value.trim()) {
            params.search = searchText.value.trim();
        }
        const response = await axios.get('/api/dose-masters', { params });
        doses.value = response.data;
    } catch (error) {
        console.error('Error fetching doses:', error);
        alert('Error loading dosage masters');
    }
    loading.value = false;
};

const selectDose = (dose) => {
    selectedDose.value = dose;
};

const editDose = (dose) => {
    editForm.value = {
        dose_id: dose.dose_id,
        dose_text: dose.dose_text,
        language: dose.language
    };
    selectedDose.value = dose;
    editModal.show();
};

const addDose = async () => {
    if (!searchText.value.trim()) {
        alert('Please enter dosage instruction');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/dose-masters', {
            dose_text: searchText.value.trim(),
            language: selectedLanguage.value
        });

        if (response.data.success) {
            searchText.value = '';
            await fetchData();
            selectedDose.value = response.data.data;
            alert('Dosage added successfully');
        }
    } catch (error) {
        console.error('Error adding dose:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error adding dosage');
        }
    }
    saving.value = false;
};

const updateDose = async () => {
    if (!editForm.value.dose_text.trim()) {
        alert('Please enter dosage instruction');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.put(`/api/dose-masters/${editForm.value.dose_id}`, {
            dose_text: editForm.value.dose_text.trim(),
            language: editForm.value.language
        });

        if (response.data.success) {
            editModal.hide();
            await fetchData();
            alert('Dosage updated successfully');
        }
    } catch (error) {
        console.error('Error updating dose:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error updating dosage');
        }
    }
    saving.value = false;
};

const confirmDelete = async (dose) => {
    if (!confirm(`Are you sure you want to delete this dosage instruction?\n\n"${dose.dose_text}"`)) {
        return;
    }

    try {
        const response = await axios.delete(`/api/dose-masters/${dose.dose_id}`);

        if (response.data.success) {
            if (selectedDose.value?.dose_id === dose.dose_id) {
                selectedDose.value = null;
            }
            await fetchData();
            alert('Dosage deleted successfully');
        }
    } catch (error) {
        console.error('Error deleting dose:', error);
        alert('Error deleting dosage');
    }
};

onMounted(() => {
    editModal = new Modal(editModalRef.value);
    fetchData();
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.table-responsive {
    max-height: 420px;
    overflow-y: auto;
}

.table thead {
    position: sticky;
    top: 0;
    background-color: #f8f9fa;
    z-index: 1;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style>
