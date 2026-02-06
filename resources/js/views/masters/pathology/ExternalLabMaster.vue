<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">External Lab Center Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add External Lab
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filters.search"
                            @input="loadLabs"
                            placeholder="Search by lab name, city...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadLabs">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadLabs">
                            <option value="20">20 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th style="min-width: 200px;">Lab Name</th>
                                <th style="min-width: 150px;">Contact Person</th>
                                <th style="min-width: 130px;">Phone</th>
                                <th style="min-width: 120px;">City</th>
                                <th style="min-width: 100px;" class="text-center">Test Types</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="8" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="labs.length === 0">
                                <td colspan="8" class="text-center text-muted py-3">
                                    No external labs found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in labs" :key="item.lab_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.lab_name }}</td>
                                <td>{{ item.contact_person || '-' }}</td>
                                <td>{{ item.telephone || item.mobile || '-' }}</td>
                                <td>{{ item.city || '-' }}</td>
                                <td class="text-center">
                                    <span v-if="item.has_patho_test" class="badge bg-primary me-1" title="Pathology">P</span>
                                    <span v-if="item.has_radio_test" class="badge bg-info me-1" title="Radiology">R</span>
                                    <span v-if="item.has_procedure_test" class="badge bg-success" title="Procedure">Pr</span>
                                    <span v-if="!item.has_patho_test && !item.has_radio_test && !item.has_procedure_test">-</span>
                                </td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editLab(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteLab(item.lab_id)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
                    <div class="text-muted small">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                            </li>
                            <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: page === pagination.current_page }">
                                <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" ref="labModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} External Lab</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveLab">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>

                            <!-- Basic Information -->
                            <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Lab Name *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.lab_name"
                                        placeholder="e.g., ABC Diagnostic Center"
                                        required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label d-block">Test Types</label>
                                    <div class="form-check form-check-inline">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="hasPathoTest"
                                            v-model="form.has_patho_test">
                                        <label class="form-check-label" for="hasPathoTest">
                                            Pathology
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="hasRadioTest"
                                            v-model="form.has_radio_test">
                                        <label class="form-check-label" for="hasRadioTest">
                                            Radiology
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="hasProcedureTest"
                                            v-model="form.has_procedure_test">
                                        <label class="form-check-label" for="hasProcedureTest">
                                            Procedure
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Contact Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.contact_person"
                                        placeholder="Contact person name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input
                                        type="email"
                                        class="form-control"
                                        v-model="form.email"
                                        placeholder="Email address">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Telephone</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.telephone"
                                        placeholder="Landline number">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Mobile</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.mobile"
                                        placeholder="Mobile number">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fax</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.fax"
                                        placeholder="Fax number">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Website</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.website"
                                        placeholder="Website URL">
                                </div>
                            </div>

                            <!-- Address Information -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Address Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.address"
                                    rows="2"
                                    placeholder="Street address"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">City</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.city"
                                        placeholder="City">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">District</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.district"
                                        placeholder="District">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">State</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.state"
                                        placeholder="State">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Pincode</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.pincode"
                                        placeholder="Postal code">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.country"
                                        placeholder="Country">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="isActive"
                                            v-model="form.is_active">
                                        <label class="form-check-label" for="isActive">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                {{ saving ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const labs = ref([]);
const filters = ref({
    search: '',
    is_active: '',
    per_page: 20,
    page: 1,
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
});

const editMode = ref(false);
const form = ref({
    lab_name: '',
    has_patho_test: false,
    has_radio_test: false,
    has_procedure_test: false,
    contact_person: '',
    telephone: '',
    mobile: '',
    fax: '',
    email: '',
    website: '',
    address: '',
    city: '',
    district: '',
    state: '',
    country: '',
    pincode: '',
    is_active: true,
});

let labModal = null;
const labModalRef = ref(null);

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

onMounted(async () => {
    await nextTick();
    if (labModalRef.value) {
        labModal = new Modal(labModalRef.value);
    }
    loadLabs();
});

const loadLabs = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/external-labs', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            labs.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            labs.value = response.data.data || response.data;
        }

        if (!Array.isArray(labs.value)) {
            labs.value = [];
        }
    } catch (err) {
        console.error('Error loading labs:', err);
        error.value = 'Failed to load external labs';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadLabs();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        lab_name: '',
        has_patho_test: false,
        has_radio_test: false,
        has_procedure_test: false,
        contact_person: '',
        telephone: '',
        mobile: '',
        fax: '',
        email: '',
        website: '',
        address: '',
        city: '',
        district: '',
        state: '',
        country: '',
        pincode: '',
        is_active: true,
    };
    if (labModal) {
        labModal.show();
    }
};

const editLab = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        lab_id: item.lab_id,
        lab_name: item.lab_name,
        has_patho_test: item.has_patho_test,
        has_radio_test: item.has_radio_test,
        has_procedure_test: item.has_procedure_test,
        contact_person: item.contact_person,
        telephone: item.telephone,
        mobile: item.mobile,
        fax: item.fax,
        email: item.email,
        website: item.website,
        address: item.address,
        city: item.city,
        district: item.district,
        state: item.state,
        country: item.country,
        pincode: item.pincode,
        is_active: item.is_active,
    };
    if (labModal) {
        labModal.show();
    }
};

const saveLab = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/external-labs/${form.value.lab_id}`, form.value);
        } else {
            await axios.post('/api/pathology/external-labs', form.value);
        }
        closeModal();
        loadLabs();
    } catch (err) {
        console.error('Error saving lab:', err);
        error.value = err.response?.data?.message || 'Failed to save external lab';
    } finally {
        saving.value = false;
    }
};

const deleteLab = async (id) => {
    if (!confirm('Are you sure you want to delete this external lab?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/external-labs/${id}`);
        loadLabs();
    } catch (err) {
        console.error('Error deleting lab:', err);
        alert(err.response?.data?.message || 'Failed to delete external lab');
    }
};

const closeModal = () => {
    if (labModal) {
        labModal.hide();
    }
};
</script>

<style scoped>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
