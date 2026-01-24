<template>
    <div>
        <p class="text-muted small mb-3">
            To Add, Type the Name into 'Search/Add Text Box' & Click Button, Next to 'Add New Box'<br>
            To Edit, Click on Name & Press INSERT Key.<br>
            To Delete, Click on Name & Press DELETE Key.
        </p>

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
                        placeholder="Type disease name"
                        @keyup.enter="addDisease"
                        @input="debouncedSearch"
                    />
                    <button class="btn btn-primary" @click="addDisease" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Add New
                    </button>
                </div>
            </div>
        </div>

        <!-- Disease List -->
        <div class="border rounded p-3" style="height: 450px; overflow-y: auto;">
            <div v-if="loading" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2"></div>Loading...
            </div>
            <div v-else-if="diseases.length === 0" class="text-center text-muted py-4">
                No diseases found
            </div>
            <div v-else>
                <div
                    v-for="disease in diseases"
                    :key="disease.disease_id"
                    class="disease-item py-2 px-3 mb-1 cursor-pointer d-flex justify-content-between align-items-center"
                    :class="{ 'bg-primary text-white': selectedDisease?.disease_id === disease.disease_id }"
                    @click="selectDisease(disease)"
                    @dblclick="editDisease(disease)"
                    @keyup.insert="editDisease(disease)"
                    @keyup.delete="confirmDelete(disease)"
                    tabindex="0"
                >
                    <span>{{ disease.disease_name }}</span>
                    <div class="btn-group btn-group-sm">
                        <button
                            class="btn btn-sm"
                            :class="selectedDisease?.disease_id === disease.disease_id ? 'btn-light' : 'btn-outline-primary'"
                            @click.stop="editDisease(disease)"
                            title="Edit"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button
                            class="btn btn-sm"
                            :class="selectedDisease?.disease_id === disease.disease_id ? 'btn-light text-danger' : 'btn-outline-danger'"
                            @click.stop="confirmDelete(disease)"
                            title="Delete"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" ref="editModalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Disease</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="updateDisease">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Disease Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="editForm.disease_name"
                                    required
                                    maxlength="255"
                                />
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
const selectedDisease = ref(null);
const diseases = ref([]);
const loading = ref(false);
const saving = ref(false);
const editModalRef = ref(null);
let editModal = null;

const editForm = ref({
    disease_id: null,
    disease_name: '',
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
        const response = await axios.get('/api/disease-masters', { params });
        diseases.value = response.data;
    } catch (error) {
        console.error('Error fetching diseases:', error);
        alert('Error loading disease masters');
    }
    loading.value = false;
};

const selectDisease = (disease) => {
    selectedDisease.value = disease;
};

const editDisease = (disease) => {
    editForm.value = {
        disease_id: disease.disease_id,
        disease_name: disease.disease_name,
        language: disease.language
    };
    selectedDisease.value = disease;
    editModal.show();
};

const addDisease = async () => {
    if (!searchText.value.trim()) {
        alert('Please enter disease name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.post('/api/disease-masters', {
            disease_name: searchText.value.trim(),
            language: selectedLanguage.value
        });

        if (response.data.success) {
            searchText.value = '';
            await fetchData();
            selectedDisease.value = response.data.data;
            alert('Disease added successfully');
        }
    } catch (error) {
        console.error('Error adding disease:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error adding disease');
        }
    }
    saving.value = false;
};

const updateDisease = async () => {
    if (!editForm.value.disease_name.trim()) {
        alert('Please enter disease name');
        return;
    }

    saving.value = true;
    try {
        const response = await axios.put(`/api/disease-masters/${editForm.value.disease_id}`, {
            disease_name: editForm.value.disease_name.trim(),
            language: editForm.value.language
        });

        if (response.data.success) {
            editModal.hide();
            await fetchData();
            alert('Disease updated successfully');
        }
    } catch (error) {
        console.error('Error updating disease:', error);
        if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error updating disease');
        }
    }
    saving.value = false;
};

const confirmDelete = async (disease) => {
    if (!confirm(`Are you sure you want to delete this disease?\n\n"${disease.disease_name}"`)) {
        return;
    }

    try {
        const response = await axios.delete(`/api/disease-masters/${disease.disease_id}`);

        if (response.data.success) {
            if (selectedDisease.value?.disease_id === disease.disease_id) {
                selectedDisease.value = null;
            }
            await fetchData();
            alert('Disease deleted successfully');
        }
    } catch (error) {
        console.error('Error deleting disease:', error);
        alert('Error deleting disease');
    }
};

onMounted(() => {
    editModal = new Modal(editModalRef.value);
    fetchData();
});
</script>

<style scoped>
.disease-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.disease-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.disease-item:focus {
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
