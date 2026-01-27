<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">GST Plan Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add GST Plan
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filters.search"
                            @input="loadGstPlans"
                            placeholder="Search by plan name...">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="activeOnly"
                                v-model="filters.active_only"
                                @change="loadGstPlans">
                            <label class="form-check-label" for="activeOnly">
                                Active Only
                            </label>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th style="min-width: 200px;">Plan Name</th>
                                <th style="min-width: 120px;" class="text-end">GST %</th>
                                <th style="min-width: 200px;">Description</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="gstPlans.length === 0">
                                <td colspan="6" class="text-center text-muted py-3">
                                    No GST plans found
                                </td>
                            </tr>
                            <tr v-else v-for="(plan, index) in gstPlans" :key="plan.gst_plan_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ plan.plan_name }}</td>
                                <td class="text-end">{{ plan.gst_percentage }}%</td>
                                <td>{{ plan.description || '-' }}</td>
                                <td class="text-center">
                                    <span :class="plan.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ plan.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editGstPlan(plan)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteGstPlan(plan.gst_plan_id)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" ref="gstPlanModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} GST Plan</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveGstPlan">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Plan Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.plan_name"
                                    placeholder="e.g., GST 9%, GST 12%"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">GST Percentage *</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    v-model="form.gst_percentage"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="e.g., 9, 12, 18"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Optional description"></textarea>
                            </div>
                            <div class="form-check">
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
import { ref, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const gstPlans = ref([]);
const filters = ref({
    search: '',
    active_only: false,
});

const editMode = ref(false);
const form = ref({
    plan_name: '',
    gst_percentage: 0,
    description: '',
    is_active: true,
});

let gstPlanModal = null;
const gstPlanModalRef = ref(null);

onMounted(async () => {
    await nextTick();
    if (gstPlanModalRef.value) {
        gstPlanModal = new Modal(gstPlanModalRef.value);
    }
    loadGstPlans();
});

const loadGstPlans = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/gst-plans', { params: filters.value });
        gstPlans.value = response.data;
    } catch (error) {
        console.error('Error loading GST plans:', error);
        alert('Failed to load GST plans');
    } finally {
        loading.value = false;
    }
};

const openAddModal = () => {
    editMode.value = false;
    form.value = {
        plan_name: '',
        gst_percentage: 0,
        description: '',
        is_active: true,
    };
    if (gstPlanModal) {
        gstPlanModal.show();
    }
};

const editGstPlan = (plan) => {
    editMode.value = true;
    form.value = {
        gst_plan_id: plan.gst_plan_id,
        plan_name: plan.plan_name,
        gst_percentage: plan.gst_percentage,
        description: plan.description,
        is_active: plan.is_active,
    };
    if (gstPlanModal) {
        gstPlanModal.show();
    }
};

const saveGstPlan = async () => {
    saving.value = true;
    try {
        if (editMode.value) {
            await axios.put(`/api/gst-plans/${form.value.gst_plan_id}`, form.value);
        } else {
            await axios.post('/api/gst-plans', form.value);
        }
        closeModal();
        loadGstPlans();
    } catch (error) {
        console.error('Error saving GST plan:', error);
        alert(error.response?.data?.message || 'Failed to save GST plan');
    } finally {
        saving.value = false;
    }
};

const deleteGstPlan = async (id) => {
    if (!confirm('Are you sure you want to delete this GST plan?')) {
        return;
    }

    try {
        await axios.delete(`/api/gst-plans/${id}`);
        loadGstPlans();
    } catch (error) {
        console.error('Error deleting GST plan:', error);
        alert('Failed to delete GST plan');
    }
};

const closeModal = () => {
    if (gstPlanModal) {
        gstPlanModal.hide();
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
