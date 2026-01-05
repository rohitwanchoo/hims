<template>
    <div>
        <div class="mb-4">
            <h5 class="mb-1">Reports</h5>
            <p class="text-muted mb-0">Generate and view hospital reports</p>
        </div>

        <div class="row g-4">
            <!-- Report Cards -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon bg-light-primary">
                                <i class="bi bi-people text-primary"></i>
                            </div>
                            <h6 class="mb-0 ms-3">Patient Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View patient registration statistics and demographics</p>
                        <button class="btn btn-sm btn-primary" @click="activeReport = 'patient'">View Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon bg-light-success">
                                <i class="bi bi-cash-stack text-success"></i>
                            </div>
                            <h6 class="mb-0 ms-3">Revenue Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View billing and collection statistics</p>
                        <button class="btn btn-sm btn-success" @click="activeReport = 'revenue'">View Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon bg-light-info">
                                <i class="bi bi-building text-info"></i>
                            </div>
                            <h6 class="mb-0 ms-3">Department Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View department-wise visit statistics</p>
                        <button class="btn btn-sm btn-info" @click="activeReport = 'department'">View Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon bg-light-warning">
                                <i class="bi bi-clipboard2-pulse text-warning"></i>
                            </div>
                            <h6 class="mb-0 ms-3">OPD Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View outpatient visit statistics</p>
                        <button class="btn btn-sm btn-warning" @click="activeReport = 'opd'">View Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon bg-light-danger">
                                <i class="bi bi-hospital text-danger"></i>
                            </div>
                            <h6 class="mb-0 ms-3">IPD Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View admission and discharge statistics</p>
                        <button class="btn btn-sm btn-danger" @click="activeReport = 'ipd'">View Report</button>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="report-icon" style="background: rgba(137, 80, 252, 0.1);">
                                <i class="bi bi-droplet" style="color: #8950fc;"></i>
                            </div>
                            <h6 class="mb-0 ms-3">Laboratory Report</h6>
                        </div>
                        <p class="text-muted small mb-3">View lab test statistics</p>
                        <button class="btn btn-sm" style="background: #8950fc; color: white;" @click="activeReport = 'lab'">View Report</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card mt-4" v-if="activeReport">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ reportTitles[activeReport] }}</h6>
                <div class="d-flex gap-2">
                    <input type="date" class="form-control form-control-sm" v-model="filters.from_date" style="width: 150px;">
                    <input type="date" class="form-control form-control-sm" v-model="filters.to_date" style="width: 150px;">
                    <button class="btn btn-sm btn-primary" @click="loadReport">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-sm btn-light" @click="activeReport = null">
                        <i class="bi bi-x"></i> Close
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading report...</p>
                </div>

                <!-- Patient Report -->
                <div v-else-if="activeReport === 'patient'">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-3 text-center">
                                <h3 class="mb-0 text-primary">{{ reportData.total || 0 }}</h3>
                                <small class="text-muted">Total Patients</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>By Gender</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr v-for="item in reportData.by_gender" :key="item.gender">
                                        <td>{{ item.gender || 'Not Specified' }}</td>
                                        <td class="text-end fw-semibold">{{ item.count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>By Blood Group</h6>
                            <table class="table table-sm">
                                <tbody>
                                    <tr v-for="item in reportData.by_blood_group" :key="item.blood_group">
                                        <td>{{ item.blood_group }}</td>
                                        <td class="text-end fw-semibold">{{ item.count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Revenue Report -->
                <div v-else-if="activeReport === 'revenue'">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h3 class="mb-0 text-primary">{{ formatCurrency(reportData.summary?.total_billed) }}</h3>
                                <small class="text-muted">Total Billed</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h3 class="mb-0 text-success">{{ formatCurrency(reportData.summary?.total_collected) }}</h3>
                                <small class="text-muted">Total Collected</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <h3 class="mb-0 text-danger">{{ formatCurrency(reportData.summary?.total_pending) }}</h3>
                                <small class="text-muted">Pending Amount</small>
                            </div>
                        </div>
                    </div>
                    <h6>Collection by Payment Mode</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payment Mode</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in reportData.by_payment_mode" :key="item.payment_mode">
                                <td>{{ item.payment_mode }}</td>
                                <td class="text-end fw-semibold">{{ formatCurrency(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Department Report -->
                <div v-else-if="activeReport === 'department'">
                    <h6>OPD Visits by Department</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th class="text-end">Visit Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in reportData.opd_by_department" :key="item.department_name">
                                <td>{{ item.department_name }}</td>
                                <td class="text-end fw-semibold">{{ item.visit_count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Generic Message -->
                <div v-else class="text-center py-5 text-muted">
                    <i class="bi bi-bar-chart" style="font-size: 3rem;"></i>
                    <p class="mt-2">Report data will appear here</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import axios from 'axios';

const activeReport = ref(null);
const loading = ref(false);
const reportData = ref({});

const filters = reactive({
    from_date: new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0],
    to_date: new Date().toISOString().split('T')[0]
});

const reportTitles = {
    patient: 'Patient Report',
    revenue: 'Revenue Report',
    department: 'Department Report',
    opd: 'OPD Report',
    ipd: 'IPD Report',
    lab: 'Laboratory Report'
};

const loadReport = async () => {
    if (!activeReport.value) return;
    loading.value = true;
    reportData.value = {};

    try {
        const params = { from_date: filters.from_date, to_date: filters.to_date };
        let endpoint = '';

        switch (activeReport.value) {
            case 'patient':
                endpoint = '/api/reports/patient-summary';
                break;
            case 'revenue':
                endpoint = '/api/reports/revenue';
                break;
            case 'department':
                endpoint = '/api/reports/department-wise';
                break;
            default:
                endpoint = '/api/reports/patient-summary';
        }

        const response = await axios.get(endpoint, { params });
        reportData.value = response.data;
    } catch (error) {
        console.error('Error loading report:', error);
    }
    loading.value = false;
};

watch(activeReport, (newVal) => {
    if (newVal) loadReport();
});

const formatCurrency = (amount) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
</script>

<style scoped>
.report-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
</style>
