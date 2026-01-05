<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-code-square me-2"></i>ICD-10 Coding</h4>
            <router-link to="/mrd" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to MRD
            </router-link>
        </div>

        <!-- Search ICD Codes -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-search me-2"></i>Search ICD-10 Codes
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" v-model="searchQuery" class="form-control"
                               placeholder="Search by code or description..." @input="searchCodes">
                    </div>
                    <div class="col-md-3">
                        <select v-model="searchCategory" class="form-select" @change="searchCodes">
                            <option value="">All Categories</option>
                            <option value="A00-B99">Infectious Diseases (A00-B99)</option>
                            <option value="C00-D49">Neoplasms (C00-D49)</option>
                            <option value="D50-D89">Blood Diseases (D50-D89)</option>
                            <option value="E00-E89">Endocrine/Metabolic (E00-E89)</option>
                            <option value="F01-F99">Mental Disorders (F01-F99)</option>
                            <option value="G00-G99">Nervous System (G00-G99)</option>
                            <option value="H00-H59">Eye Diseases (H00-H59)</option>
                            <option value="H60-H95">Ear Diseases (H60-H95)</option>
                            <option value="I00-I99">Circulatory System (I00-I99)</option>
                            <option value="J00-J99">Respiratory System (J00-J99)</option>
                            <option value="K00-K95">Digestive System (K00-K95)</option>
                            <option value="L00-L99">Skin Diseases (L00-L99)</option>
                            <option value="M00-M99">Musculoskeletal (M00-M99)</option>
                            <option value="N00-N99">Genitourinary (N00-N99)</option>
                            <option value="O00-O9A">Pregnancy (O00-O9A)</option>
                            <option value="P00-P96">Perinatal (P00-P96)</option>
                            <option value="Q00-Q99">Congenital (Q00-Q99)</option>
                            <option value="R00-R99">Symptoms/Signs (R00-R99)</option>
                            <option value="S00-T88">Injury/Poisoning (S00-T88)</option>
                            <option value="V00-Y99">External Causes (V00-Y99)</option>
                            <option value="Z00-Z99">Health Status (Z00-Z99)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button @click="searchCodes" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </div>

                <!-- Search Results -->
                <div v-if="searchResults.length > 0" class="mt-3">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="code in searchResults" :key="code.code">
                                    <td><code>{{ code.code }}</code></td>
                                    <td>{{ code.description }}</td>
                                    <td><small class="text-muted">{{ code.category }}</small></td>
                                    <td>
                                        <button @click="addToSelection(code)" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Coding Assignment -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-person-badge me-2"></i>Assign Codes to Patient Visit
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Select Patient Visit</label>
                            <select v-model="selectedVisit" class="form-select" @change="loadVisitCodes">
                                <option value="">Select a visit...</option>
                                <option v-for="visit in recentVisits" :key="visit.visit_id" :value="visit.visit_id">
                                    {{ visit.patient_name }} - {{ formatDate(visit.visit_date) }} ({{ visit.visit_type }})
                                </option>
                            </select>
                        </div>

                        <div v-if="selectedVisit">
                            <h6>Selected Codes:</h6>
                            <div v-if="selectedCodes.length === 0" class="text-muted py-3 text-center">
                                No codes selected. Search and add codes above.
                            </div>
                            <div v-else class="list-group mb-3">
                                <div v-for="(code, index) in selectedCodes" :key="code.code"
                                     class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <code class="me-2">{{ code.code }}</code>
                                        <span>{{ code.description }}</span>
                                        <span v-if="index === 0" class="badge bg-primary ms-2">Primary</span>
                                    </div>
                                    <div>
                                        <button v-if="index > 0" @click="setPrimary(index)" class="btn btn-sm btn-outline-primary me-1" title="Set as Primary">
                                            <i class="bi bi-star"></i>
                                        </button>
                                        <button @click="removeCode(index)" class="btn btn-sm btn-outline-danger" title="Remove">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button @click="saveCodes" class="btn btn-success w-100" :disabled="saving || selectedCodes.length === 0">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-check-lg me-1"></i>Save Codes
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Common Codes Quick Access -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="bi bi-lightning me-2"></i>Frequently Used Codes
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div v-for="code in commonCodes" :key="code.code" class="col-md-6">
                                <button @click="addToSelection(code)" class="btn btn-outline-secondary btn-sm w-100 text-start">
                                    <code>{{ code.code }}</code> - {{ code.short_desc }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Coded Visits -->
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-clock-history me-2"></i>Recently Coded Visits
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Patient</th>
                                    <th>Visit Date</th>
                                    <th>Primary Code</th>
                                    <th>Coded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="visit in recentCodedVisits" :key="visit.visit_id">
                                    <td>{{ visit.patient_name }}</td>
                                    <td>{{ formatDate(visit.visit_date) }}</td>
                                    <td><code>{{ visit.primary_icd_code }}</code></td>
                                    <td>{{ visit.coded_by }}</td>
                                </tr>
                                <tr v-if="recentCodedVisits.length === 0">
                                    <td colspan="4" class="text-center text-muted py-3">No recent coded visits</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const searchQuery = ref('');
const searchCategory = ref('');
const searchResults = ref([]);
const selectedVisit = ref('');
const selectedCodes = ref([]);
const recentVisits = ref([]);
const recentCodedVisits = ref([]);
const saving = ref(false);

const commonCodes = ref([
    { code: 'J06.9', description: 'Acute upper respiratory infection, unspecified', short_desc: 'URI' },
    { code: 'J18.9', description: 'Pneumonia, unspecified organism', short_desc: 'Pneumonia' },
    { code: 'K29.7', description: 'Gastritis, unspecified', short_desc: 'Gastritis' },
    { code: 'I10', description: 'Essential (primary) hypertension', short_desc: 'Hypertension' },
    { code: 'E11.9', description: 'Type 2 diabetes mellitus without complications', short_desc: 'Diabetes T2' },
    { code: 'N39.0', description: 'Urinary tract infection, site not specified', short_desc: 'UTI' },
    { code: 'R50.9', description: 'Fever, unspecified', short_desc: 'Fever' },
    { code: 'M54.5', description: 'Low back pain', short_desc: 'Back Pain' },
    { code: 'R51', description: 'Headache', short_desc: 'Headache' },
    { code: 'K30', description: 'Functional dyspepsia', short_desc: 'Dyspepsia' }
]);

const searchCodes = async () => {
    if (!searchQuery.value && !searchCategory.value) {
        searchResults.value = [];
        return;
    }

    try {
        const params = new URLSearchParams();
        if (searchQuery.value) params.append('search', searchQuery.value);
        if (searchCategory.value) params.append('category', searchCategory.value);

        const response = await axios.get(`/api/mrd/icd-codes?${params.toString()}`);
        searchResults.value = response.data.codes || [];
    } catch (error) {
        console.error('Failed to search codes:', error);
        // Fallback: filter common codes locally
        if (searchQuery.value) {
            searchResults.value = commonCodes.value.filter(c =>
                c.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                c.description.toLowerCase().includes(searchQuery.value.toLowerCase())
            );
        }
    }
};

const loadRecentVisits = async () => {
    try {
        const response = await axios.get('/api/mrd/uncoded-visits?limit=50');
        recentVisits.value = response.data.visits || [];
    } catch (error) {
        console.error('Failed to load visits:', error);
    }
};

const loadRecentCodedVisits = async () => {
    try {
        const response = await axios.get('/api/mrd/coded-visits?limit=10');
        recentCodedVisits.value = response.data.visits || [];
    } catch (error) {
        console.error('Failed to load coded visits:', error);
    }
};

const loadVisitCodes = async () => {
    if (!selectedVisit.value) {
        selectedCodes.value = [];
        return;
    }

    try {
        const response = await axios.get(`/api/mrd/visits/${selectedVisit.value}/codes`);
        selectedCodes.value = response.data.codes || [];
    } catch (error) {
        console.error('Failed to load visit codes:', error);
        selectedCodes.value = [];
    }
};

const addToSelection = (code) => {
    if (!selectedCodes.value.find(c => c.code === code.code)) {
        selectedCodes.value.push({ ...code });
    }
};

const removeCode = (index) => {
    selectedCodes.value.splice(index, 1);
};

const setPrimary = (index) => {
    const code = selectedCodes.value.splice(index, 1)[0];
    selectedCodes.value.unshift(code);
};

const saveCodes = async () => {
    if (!selectedVisit.value || selectedCodes.value.length === 0) return;

    saving.value = true;
    try {
        await axios.post(`/api/mrd/visits/${selectedVisit.value}/codes`, {
            codes: selectedCodes.value.map((c, i) => ({
                icd_code: c.code,
                description: c.description,
                is_primary: i === 0
            }))
        });
        alert('Codes saved successfully');
        loadRecentCodedVisits();
        loadRecentVisits();
    } catch (error) {
        console.error('Failed to save codes:', error);
        alert(error.response?.data?.message || 'Failed to save codes');
    } finally {
        saving.value = false;
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

onMounted(() => {
    loadRecentVisits();
    loadRecentCodedVisits();
});
</script>
