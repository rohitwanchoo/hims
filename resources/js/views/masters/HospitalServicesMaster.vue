<template>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Hospital Services</h5>
                <button class="btn btn-primary btn-sm" @click="openAddModal">
                    <i class="bi bi-plus-circle"></i> Add Service
                </button>
            </div>

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Class Name</label>
                        <select class="form-select form-select-sm" v-model="filters.insurance_id" @change="loadServices">
                            <option value="">All</option>
                            <option value="private">PRIVATE</option>
                            <option v-for="ins in insuranceCompanies" :key="ins.insurance_id" :value="ins.insurance_id">
                                {{ ins.company_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cost Head</label>
                        <select class="form-select form-select-sm" v-model="filters.cost_head_id" @change="loadServices">
                            <option value="">All</option>
                            <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                {{ ch.cost_head_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search Services</label>
                        <input type="text" class="form-control form-control-sm" v-model="filters.search" @input="loadServices" placeholder="Search by service name...">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-secondary btn-sm me-2" @click="clearFilters">Clear</button>
                        <button class="btn btn-info btn-sm" @click="openCostHeadModal">
                            <i class="bi bi-list-task"></i> Manage Cost Heads
                        </button>
                    </div>
                </div>

                <!-- Services Table -->
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th style="min-width: 200px;">Service Name</th>
                                <th style="min-width: 120px;">Cost Head</th>
                                <th style="min-width: 150px;">Class Name</th>
                                <th style="min-width: 120px;" class="text-end">Base Price</th>
                                <th style="min-width: 150px;" class="text-center">Room/Bed Prices</th>
                                <th style="min-width: 150px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="services.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No services found. Click "Add Service" to create one.
                                </td>
                            </tr>
                            <tr v-else v-for="(service, index) in services" :key="service.hospital_service_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ service.service_name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ service.cost_head_name }}</span>
                                </td>
                                <td>{{ service.insurance_company_name || 'PRIVATE' }}</td>
                                <td class="text-end">₹{{ parseFloat(service.base_price).toFixed(2) }}</td>
                                <td class="text-center">
                                    <span v-if="service.cost_head_type === 'ipd_services'">
                                        <span
                                            v-if="service.prices && service.prices.length > 0"
                                            class="badge bg-success"
                                            style="cursor: pointer;"
                                            @click="showPriceDetails(service)"
                                            title="Click to view details"
                                        >
                                            <i class="bi bi-info-circle"></i> {{ service.prices.length }} Price{{ service.prices.length > 1 ? 's' : '' }} Set
                                        </span>
                                        <span v-else class="badge bg-warning text-dark">
                                            Not Set
                                        </span>
                                    </span>
                                    <span v-else class="text-muted">N/A</span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="service.cost_head_type === 'ipd_services'"
                                        class="btn btn-sm btn-outline-success me-1"
                                        @click="openSetPricesModal(service)"
                                        title="Set Room/Bed Prices"
                                    >
                                        <i class="bi bi-coin"></i> Set Prices
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editService(service)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteService(service)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add/Edit Service Modal -->
        <div class="modal fade" ref="serviceModalRef" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit Service' : 'Add Service' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveService">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Class Name *</label>
                                    <select class="form-select" v-model="form.insurance_id" required>
                                        <option value="">PRIVATE</option>
                                        <option v-for="ins in insuranceCompanies" :key="ins.insurance_id" :value="ins.insurance_id">
                                            {{ ins.company_name }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Select class or leave as PRIVATE</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cost Head *</label>
                                    <select class="form-select" v-model="form.cost_head_id" @change="onCostHeadChange" required>
                                        <option value="">Select Cost Head</option>
                                        <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                            {{ ch.cost_head_name }} ({{ ch.cost_head_type.toUpperCase().replace('_', ' ') }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Service Name *</label>
                                    <input type="text" class="form-control" v-model="form.service_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Base Price *</label>
                                    <input type="number" class="form-control" v-model="form.base_price" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Day Emergency Rate</label>
                                    <input type="number" class="form-control" v-model="form.day_emergency_rate" step="0.01" min="0" placeholder="0.00">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Night Emergency Rate</label>
                                    <input type="number" class="form-control" v-model="form.night_emergency_rate" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">From Date</label>
                                    <input type="date" class="form-control" v-model="form.from_date">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">To Date</label>
                                    <input type="date" class="form-control" v-model="form.to_date">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price Unit *</label>
                                    <select class="form-select" v-model="form.price_unit" required>
                                        <option value="per_service">Per Service</option>
                                        <option value="per_day">Per Day</option>
                                        <option value="per_hour">Per Hour</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" v-model="form.is_active">
                                        <option :value="true">Active</option>
                                        <option :value="false">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="freeFollowup" v-model="form.is_free_followup">
                                        <label class="form-check-label" for="freeFollowup">
                                            Free Follow-up (No charges for follow-up visits)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="qtyRateNotRequired" v-model="form.qty_rate_not_required">
                                        <label class="form-check-label" for="qtyRateNotRequired">
                                            Qty & Rate Not Required (Only show service name in billing)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="border-top pt-3 mb-3">
                                <h6 class="mb-3">GST Applicable</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">GST Plan</label>
                                        <select class="form-select" v-model="form.gst_plan_id">
                                            <option value="">No GST</option>
                                            <option v-if="gstPlans && gstPlans.length === 0" disabled>Loading GST Plans...</option>
                                            <option v-for="gst in gstPlans" :key="gst.gst_plan_id" :value="gst.gst_plan_id">
                                                {{ gst.plan_name }} ({{ gst.gst_percentage }}%)
                                            </option>
                                        </select>
                                        <small v-if="gstPlans" class="text-muted">{{ gstPlans.length }} GST plan(s) available</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">GST Above Amount</label>
                                        <input type="number" class="form-control" v-model="form.gst_above_amount" step="0.01" min="0" placeholder="Apply GST if amount exceeds">
                                        <small class="text-muted">GST will be applied only if the total amount is above this value</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" v-model="form.description" rows="2"></textarea>
                            </div>

                            <!-- Room/Bed Selection (only if IPD Services) -->
                            <div v-if="selectedCostHeadType === 'ipd_services'" class="border-top pt-3">
                                <h6 class="mb-3">Set Prices for Rooms/Beds</h6>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> For IPD Services, you can set specific prices for different rooms and beds.
                                    Leave blank to use base price.
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Rooms</h6>
                                        <div class="mb-2" v-for="room in roomsAndBeds" :key="room.room_id">
                                            <label class="form-label small">{{ room.room_name }}</label>
                                            <input
                                                type="number"
                                                class="form-control form-control-sm"
                                                v-model="roomPrices[room.room_id]"
                                                step="0.01"
                                                min="0"
                                                placeholder="Price for this room"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Beds</h6>
                                        <div style="max-height: 300px; overflow-y: auto;">
                                            <div v-for="room in roomsAndBeds" :key="'beds-' + room.room_id">
                                                <div class="mb-2" v-for="bed in room.beds" :key="bed.bed_id">
                                                    <label class="form-label small">{{ room.room_name }} - Bed {{ bed.bed_number }}</label>
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm"
                                                        v-model="bedPrices[bed.bed_id]"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Price for this bed"
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveService" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ saving ? 'Saving...' : (editMode ? 'Update' : 'Save') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Set Room/Bed Prices Modal -->
        <div class="modal fade" ref="setPricesModalRef" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Set Room/Bed Prices - {{ selectedService ? selectedService.service_name : '' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Select Ward *</label>
                                <select class="form-select" v-model="selectedWardId" @change="loadWardRooms">
                                    <option value="">-- Select Ward --</option>
                                    <option v-for="ward in wards" :key="ward.ward_id" :value="ward.ward_id">
                                        {{ ward.ward_name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="selectedWardId && wardRooms.length > 0" class="mt-4">
                            <div class="row">
                                <!-- Left Side: Rooms List -->
                                <div class="col-md-4">
                                    <h6 class="mb-3">Rooms</h6>
                                    <div class="list-group">
                                        <button
                                            type="button"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            :class="{ 'active': selectedRoomId === room.room_id }"
                                            v-for="room in wardRooms"
                                            :key="room.room_id"
                                            @click="selectRoom(room)"
                                        >
                                            <div>
                                                <strong>{{ room.room_name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ room.beds ? room.beds.length : 0 }} beds</small>
                                            </div>
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Right Side: Room and Bed Prices -->
                                <div class="col-md-8">
                                    <div v-if="selectedRoom">
                                        <h6 class="mb-3">{{ selectedRoom.room_name }} - Set Prices</h6>

                                        <!-- Room Price -->
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-5">
                                                        <label class="form-label mb-0">
                                                            <strong>Room Price</strong>
                                                            <br>
                                                            <small class="text-muted">Applies to all beds</small>
                                                        </label>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <div class="input-group">
                                                            <span class="input-group-text">₹</span>
                                                            <input
                                                                type="number"
                                                                class="form-control"
                                                                v-model="tempPrices['room_' + selectedRoom.room_id]"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder="Enter room price"
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Bed Prices -->
                                        <div class="card">
                                            <div class="card-header bg-white">
                                                <strong>Individual Bed Prices</strong>
                                                <small class="text-muted ms-2">(Override room price)</small>
                                            </div>
                                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3" v-for="bed in selectedRoom.beds" :key="bed.bed_id">
                                                        <label class="form-label small mb-1">
                                                            <i class="bi bi-bed"></i> Bed {{ bed.bed_number }}
                                                        </label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">₹</span>
                                                            <input
                                                                type="number"
                                                                class="form-control"
                                                                v-model="tempPrices['bed_' + bed.bed_id]"
                                                                step="0.01"
                                                                min="0"
                                                                placeholder="Price"
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="bi bi-info-circle"></i>
                                            <small>Individual bed prices will override the room price. Leave blank to use room price.</small>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-muted py-5">
                                        <i class="bi bi-arrow-left" style="font-size: 2rem;"></i>
                                        <p class="mt-2">Select a room from the left to set prices</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="selectedWardId && wardRooms.length === 0" class="alert alert-warning">
                            No rooms found in this ward.
                        </div>

                        <div v-else class="alert alert-info">
                            Please select a ward to view and set prices for rooms and beds.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="savePrices"
                            :disabled="savingPrices || !selectedWardId"
                        >
                            <span v-if="savingPrices" class="spinner-border spinner-border-sm me-1"></span>
                            {{ savingPrices ? 'Saving...' : 'Save Prices' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Price Details Modal -->
        <div class="modal fade" ref="priceDetailsModalRef" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Price Details - {{ priceDetailsService ? priceDetailsService.service_name : '' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="priceDetailsService && priceDetailsService.prices && priceDetailsService.prices.length > 0">
                            <h6 class="mb-3">Configured Prices</h6>

                            <!-- Room Prices -->
                            <div class="mb-4" v-if="roomPricesList.length > 0">
                                <h6 class="text-muted mb-2">Room Prices</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Room Name</th>
                                                <th>Ward</th>
                                                <th>Total Beds</th>
                                                <th class="text-end">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="price in roomPricesList" :key="'room-' + price.room_id">
                                                <td><strong>{{ price.room_name }}</strong></td>
                                                <td>{{ price.ward_name }}</td>
                                                <td>{{ price.bed_count || 'N/A' }}</td>
                                                <td class="text-end">
                                                    <strong class="text-success">₹{{ parseFloat(price.price).toFixed(2) }}</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Bed Prices -->
                            <div v-if="bedPricesList.length > 0">
                                <h6 class="text-muted mb-2">Bed Prices</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Room Name</th>
                                                <th>Bed Number</th>
                                                <th>Ward</th>
                                                <th class="text-end">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="price in bedPricesList" :key="'bed-' + price.bed_id">
                                                <td>{{ price.room_name }}</td>
                                                <td>Bed {{ price.bed_number }}</td>
                                                <td>{{ price.ward_name }}</td>
                                                <td class="text-end">
                                                    <strong class="text-success">₹{{ parseFloat(price.price).toFixed(2) }}</strong>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Total:</strong> {{ roomPricesList.length }} room price(s) + {{ bedPricesList.length }} bed price(s) = {{ priceDetailsService.prices.length }} total price(s)
                            </div>
                        </div>
                        <div v-else class="alert alert-warning">
                            No prices configured for this service.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="openSetPricesModalFromDetails"
                        >
                            <i class="bi bi-pencil"></i> Edit Prices
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Head Management Modal -->
        <div class="modal fade" ref="costHeadModalRef" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Cost Heads</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <button class="btn btn-sm btn-primary mb-3" @click="openAddCostHeadForm">
                            <i class="bi bi-plus"></i> Add Cost Head
                        </button>

                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ch in costHeads" :key="ch.cost_head_id">
                                    <td>{{ ch.cost_head_code }}</td>
                                    <td>{{ ch.cost_head_name }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ ch.cost_head_type.replace('_', ' ').toUpperCase() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="ch.is_active ? 'bg-success' : 'bg-danger'">
                                            {{ ch.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" @click="editCostHead(ch)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteCostHead(ch)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Cost Head Form (inline) -->
        <div v-if="showCostHeadForm" class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0">{{ costHeadEditMode ? 'Edit Cost Head' : 'Add Cost Head' }}</h6>
            </div>
            <div class="card-body">
                <form @submit.prevent="saveCostHead">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Code *</label>
                            <input type="text" class="form-control" v-model="costHeadForm.cost_head_code" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" v-model="costHeadForm.cost_head_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select" v-model="costHeadForm.cost_head_type" required>
                                <option value="ipd_services">IPD Services</option>
                                <option value="opd_services">OPD Services</option>
                                <option value="lab_services">Lab Services</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="radiology">Radiology</option>
                                <option value="procedure">Procedure</option>
                                <option value="others">Others</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" v-model="costHeadForm.description" rows="2"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            {{ costHeadEditMode ? 'Update' : 'Save' }}
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" @click="showCostHeadForm = false">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, nextTick } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

export default {
    name: 'HospitalServicesMaster',
    setup() {
        const loading = ref(false);
        const saving = ref(false);
        const services = ref([]);
        const insuranceCompanies = ref([]);
        const costHeads = ref([]);
        const gstPlans = ref([]);
        const roomsAndBeds = ref([]);
        const allRooms = ref([]);
        const allBeds = ref([]);

        const filters = ref({
            insurance_id: '',
            cost_head_id: '',
            search: '',
        });

        const editMode = ref(false);
        const form = ref({
            insurance_id: '',
            cost_head_id: '',
            service_name: '',
            description: '',
            from_date: '',
            to_date: '',
            base_price: 0,
            day_emergency_rate: 0,
            night_emergency_rate: 0,
            price_unit: 'per_service',
            is_active: true,
            is_free_followup: false,
            qty_rate_not_required: false,
            gst_plan_id: '',
            gst_above_amount: null,
        });

        const roomPrices = ref({});
        const bedPrices = ref({});
        const selectedCostHeadType = ref('');

        // Cost Head Management
        const showCostHeadForm = ref(false);
        const costHeadEditMode = ref(false);
        const costHeadForm = ref({
            cost_head_code: '',
            cost_head_name: '',
            cost_head_type: 'opd_services',
            description: '',
            is_active: true,
        });

        // Set Prices Modal
        const selectedService = ref(null);
        const selectedWardId = ref('');
        const wards = ref([]);
        const wardRooms = ref([]);
        const selectedRoomId = ref(null);
        const selectedRoom = ref(null);
        const tempPrices = ref({});
        const savingPrices = ref(false);

        // Price Details Modal
        const priceDetailsService = ref(null);
        const roomPricesList = ref([]);
        const bedPricesList = ref([]);

        let serviceModal = null;
        let costHeadModal = null;
        let setPricesModal = null;
        let priceDetailsModal = null;
        const serviceModalRef = ref(null);
        const costHeadModalRef = ref(null);
        const setPricesModalRef = ref(null);
        const priceDetailsModalRef = ref(null);

        onMounted(async () => {
            await nextTick();

            if (serviceModalRef.value) {
                serviceModal = new Modal(serviceModalRef.value);
            }
            if (costHeadModalRef.value) {
                costHeadModal = new Modal(costHeadModalRef.value);
            }
            if (setPricesModalRef.value) {
                setPricesModal = new Modal(setPricesModalRef.value);
            }
            if (priceDetailsModalRef.value) {
                priceDetailsModal = new Modal(priceDetailsModalRef.value);
            }

            await loadMasterData();
            await loadServices();
        });

        const loadMasterData = async () => {
            try {
                const [insRes, costRes, roomsRes, wardsRes, gstRes] = await Promise.all([
                    axios.get('/api/insurance-companies-active'),
                    axios.get('/api/cost-heads?active_only=1'),
                    axios.get('/api/hospital-services-rooms-beds'),
                    axios.get('/api/wards'),
                    axios.get('/api/gst-plans/active'),
                ]);

                console.log('Insurance Companies Response:', insRes.data);
                console.log('Cost Heads Response:', costRes.data);
                console.log('Rooms Response:', roomsRes.data);
                console.log('Wards Response:', wardsRes.data);
                console.log('GST Plans Response:', gstRes.data);

                insuranceCompanies.value = insRes.data || [];
                costHeads.value = costRes.data || [];
                roomsAndBeds.value = roomsRes.data || [];
                wards.value = wardsRes.data || [];
                gstPlans.value = gstRes.data || [];

                console.log('Loaded GST Plans count:', gstPlans.value.length);
                console.log('GST Plans data:', gstPlans.value);

                // Extract all rooms and beds for table headers
                allRooms.value = roomsRes.data || [];
                allBeds.value = [];
                if (roomsRes.data && Array.isArray(roomsRes.data)) {
                    roomsRes.data.forEach(room => {
                        if (room.beds && Array.isArray(room.beds)) {
                            // Add room name to each bed for display
                            room.beds.forEach(bed => {
                                allBeds.value.push({
                                    ...bed,
                                    room_name: room.room_name
                                });
                            });
                        }
                    });
                }

                console.log('Insurance Companies Count:', insuranceCompanies.value.length);
                console.log('Cost Heads Count:', costHeads.value.length);
            } catch (error) {
                console.error('Error loading master data:', error);
                if (error.response) {
                    console.error('Response data:', error.response.data);
                    console.error('Response status:', error.response.status);
                }
                alert('Failed to load master data: ' + (error.response?.data?.message || error.message));
            }
        };

        const loadServices = async () => {
            loading.value = true;
            try {
                const params = new URLSearchParams();
                if (filters.value.insurance_id) params.append('insurance_id', filters.value.insurance_id);
                if (filters.value.cost_head_id) params.append('cost_head_id', filters.value.cost_head_id);
                if (filters.value.search) params.append('search', filters.value.search);

                const response = await axios.get('/api/hospital-services?' + params.toString());
                services.value = response.data || [];
            } catch (error) {
                console.error('Error loading services:', error);
            } finally {
                loading.value = false;
            }
        };

        const clearFilters = () => {
            filters.value = {
                insurance_id: '',
                cost_head_id: '',
                search: '',
            };
            loadServices();
        };

        const openAddModal = () => {
            editMode.value = false;
            form.value = {
                insurance_id: '',
                cost_head_id: '',
                service_name: '',
                description: '',
                from_date: '',
                to_date: '',
                base_price: 0,
                day_emergency_rate: 0,
                night_emergency_rate: 0,
                price_unit: 'per_service',
                is_active: true,
                is_free_followup: false,
                qty_rate_not_required: false,
                gst_plan_id: '',
                gst_above_amount: null,
            };
            roomPrices.value = {};
            bedPrices.value = {};
            selectedCostHeadType.value = '';
            if (serviceModal) {
                serviceModal.show();
            } else {
                console.error('Service modal not initialized');
            }
        };

        const editService = (service) => {
            editMode.value = true;
            form.value = {
                hospital_service_id: service.hospital_service_id,
                insurance_id: service.insurance_id || '',
                cost_head_id: service.cost_head_id,
                service_name: service.service_name,
                description: service.description,
                from_date: service.from_date || '',
                to_date: service.to_date || '',
                base_price: service.base_price,
                day_emergency_rate: service.day_emergency_rate || 0,
                night_emergency_rate: service.night_emergency_rate || 0,
                price_unit: service.price_unit,
                is_active: service.is_active,
                is_free_followup: service.is_free_followup || false,
                qty_rate_not_required: service.qty_rate_not_required || false,
                gst_plan_id: service.gst_plan_id || '',
                gst_above_amount: service.gst_above_amount || null,
            };

            // Load existing prices
            roomPrices.value = {};
            bedPrices.value = {};
            if (service.prices) {
                service.prices.forEach(price => {
                    if (price.room_id) {
                        roomPrices.value[price.room_id] = price.price;
                    }
                    if (price.bed_id) {
                        bedPrices.value[price.bed_id] = price.price;
                    }
                });
            }

            selectedCostHeadType.value = service.cost_head_type;
            if (serviceModal) {
                serviceModal.show();
            } else {
                console.error('Service modal not initialized');
            }
        };

        const onCostHeadChange = () => {
            const selectedCostHead = costHeads.value.find(ch => ch.cost_head_id == form.value.cost_head_id);
            selectedCostHeadType.value = selectedCostHead ? selectedCostHead.cost_head_type : '';
        };

        const saveService = async () => {
            saving.value = true;
            try {
                const payload = { ...form.value };

                // Add prices if IPD Services
                if (selectedCostHeadType.value === 'ipd_services') {
                    payload.prices = [];

                    // Add room prices
                    Object.keys(roomPrices.value).forEach(roomId => {
                        if (roomPrices.value[roomId] && parseFloat(roomPrices.value[roomId]) > 0) {
                            payload.prices.push({
                                room_id: roomId,
                                bed_id: null,
                                price: parseFloat(roomPrices.value[roomId]),
                            });
                        }
                    });

                    // Add bed prices
                    Object.keys(bedPrices.value).forEach(bedId => {
                        if (bedPrices.value[bedId] && parseFloat(bedPrices.value[bedId]) > 0) {
                            payload.prices.push({
                                room_id: null,
                                bed_id: bedId,
                                price: parseFloat(bedPrices.value[bedId]),
                            });
                        }
                    });
                }

                if (editMode.value) {
                    await axios.put(`/api/hospital-services/${form.value.hospital_service_id}`, payload);
                } else {
                    await axios.post('/api/hospital-services', payload);
                }

                if (serviceModal) {
                    serviceModal.hide();
                }
                await loadServices();
            } catch (error) {
                console.error('Error saving service:', error);
                alert('Failed to save service: ' + (error.response?.data?.message || error.message));
            } finally {
                saving.value = false;
            }
        };

        const deleteService = async (service) => {
            if (!confirm(`Are you sure you want to delete "${service.service_name}"?`)) return;

            try {
                await axios.delete(`/api/hospital-services/${service.hospital_service_id}`);
                await loadServices();
            } catch (error) {
                console.error('Error deleting service:', error);
                alert('Failed to delete service: ' + (error.response?.data?.message || error.message));
            }
        };

        // Price Details Functions
        const showPriceDetails = (service) => {
            priceDetailsService.value = service;
            roomPricesList.value = [];
            bedPricesList.value = [];

            if (service.prices && Array.isArray(service.prices)) {
                // Separate room and bed prices and enrich with names
                service.prices.forEach(price => {
                    if (price.room_id && !price.bed_id) {
                        // Find room details from roomsAndBeds
                        const room = roomsAndBeds.value.find(r => r.room_id == price.room_id);
                        if (room) {
                            roomPricesList.value.push({
                                ...price,
                                room_name: room.room_name,
                                ward_name: room.ward ? room.ward.ward_name : 'N/A',
                                bed_count: room.beds ? room.beds.length : 0
                            });
                        }
                    } else if (price.bed_id) {
                        // Find bed details from roomsAndBeds
                        let bedInfo = null;
                        for (const room of roomsAndBeds.value) {
                            if (room.beds && Array.isArray(room.beds)) {
                                const bed = room.beds.find(b => b.bed_id == price.bed_id);
                                if (bed) {
                                    bedInfo = {
                                        ...price,
                                        bed_number: bed.bed_number,
                                        room_name: room.room_name,
                                        ward_name: room.ward ? room.ward.ward_name : 'N/A'
                                    };
                                    break;
                                }
                            }
                        }
                        if (bedInfo) {
                            bedPricesList.value.push(bedInfo);
                        }
                    }
                });
            }

            if (priceDetailsModal) {
                priceDetailsModal.show();
            } else {
                console.error('Price details modal not initialized');
            }
        };

        const openSetPricesModalFromDetails = () => {
            if (priceDetailsModal) {
                priceDetailsModal.hide();
            }
            if (priceDetailsService.value) {
                openSetPricesModal(priceDetailsService.value);
            }
        };

        // Set Prices Modal Functions
        const openSetPricesModal = async (service) => {
            selectedService.value = service;
            selectedWardId.value = '';
            wardRooms.value = [];
            selectedRoomId.value = null;
            selectedRoom.value = null;
            tempPrices.value = {};

            // Load existing prices into tempPrices
            if (service.prices && Array.isArray(service.prices)) {
                service.prices.forEach(price => {
                    if (price.room_id && !price.bed_id) {
                        tempPrices.value['room_' + price.room_id] = price.price;
                    } else if (price.bed_id) {
                        tempPrices.value['bed_' + price.bed_id] = price.price;
                    }
                });
            }

            if (setPricesModal) {
                setPricesModal.show();
            } else {
                console.error('Set prices modal not initialized');
            }
        };

        const loadWardRooms = async () => {
            if (!selectedWardId.value) {
                wardRooms.value = [];
                selectedRoomId.value = null;
                selectedRoom.value = null;
                return;
            }

            try {
                wardRooms.value = roomsAndBeds.value.filter(room => room.ward_id == selectedWardId.value);
                console.log('Ward Rooms:', wardRooms.value);

                // Auto-select first room if available
                if (wardRooms.value.length > 0) {
                    selectRoom(wardRooms.value[0]);
                }
            } catch (error) {
                console.error('Error loading ward rooms:', error);
                alert('Failed to load ward rooms');
            }
        };

        const selectRoom = (room) => {
            selectedRoomId.value = room.room_id;
            selectedRoom.value = room;
        };

        const savePrices = async () => {
            if (!selectedService.value || !selectedWardId.value) {
                alert('Please select a ward');
                return;
            }

            savingPrices.value = true;
            try {
                const prices = [];

                // Collect all prices from tempPrices
                Object.keys(tempPrices.value).forEach(key => {
                    const value = tempPrices.value[key];
                    if (value && parseFloat(value) > 0) {
                        if (key.startsWith('room_')) {
                            const roomId = key.replace('room_', '');
                            prices.push({
                                room_id: roomId,
                                bed_id: null,
                                price: parseFloat(value)
                            });
                        } else if (key.startsWith('bed_')) {
                            const bedId = key.replace('bed_', '');
                            prices.push({
                                room_id: null,
                                bed_id: bedId,
                                price: parseFloat(value)
                            });
                        }
                    }
                });

                // Update the service with new prices
                await axios.put(`/api/hospital-services/${selectedService.value.hospital_service_id}`, {
                    ...selectedService.value,
                    prices: prices
                });

                if (setPricesModal) {
                    setPricesModal.hide();
                }
                await loadServices();
                alert('Prices saved successfully!');
            } catch (error) {
                console.error('Error saving prices:', error);
                alert('Failed to save prices: ' + (error.response?.data?.message || error.message));
            } finally {
                savingPrices.value = false;
            }
        };

        const getRoomPrice = (service, roomId) => {
            const price = service.prices?.find(p => p.room_id == roomId && !p.bed_id);
            return price ? parseFloat(price.price).toFixed(2) : '0.00';
        };

        const getBedPrice = (service, bedId) => {
            const price = service.prices?.find(p => p.bed_id == bedId);
            return price ? parseFloat(price.price).toFixed(2) : '0.00';
        };

        const updateRoomPrice = async (service, roomId, newPrice) => {
            try {
                const price = service.prices?.find(p => p.room_id == roomId && !p.bed_id);
                const priceValue = parseFloat(newPrice) || 0;

                if (price) {
                    await axios.put(`/api/hospital-services/${service.hospital_service_id}/prices/${price.price_id}`, {
                        price: priceValue,
                    });
                } else {
                    // Create new price
                    await axios.post('/api/hospital-services', {
                        ...service,
                        prices: [{ room_id: roomId, bed_id: null, price: priceValue }],
                    });
                }

                await loadServices();
            } catch (error) {
                console.error('Error updating room price:', error);
            }
        };

        const updateBedPrice = async (service, bedId, newPrice) => {
            try {
                const price = service.prices?.find(p => p.bed_id == bedId);
                const priceValue = parseFloat(newPrice) || 0;

                if (price) {
                    await axios.put(`/api/hospital-services/${service.hospital_service_id}/prices/${price.price_id}`, {
                        price: priceValue,
                    });
                } else {
                    // Create new price
                    await axios.post('/api/hospital-services', {
                        ...service,
                        prices: [{ room_id: null, bed_id: bedId, price: priceValue }],
                    });
                }

                await loadServices();
            } catch (error) {
                console.error('Error updating bed price:', error);
            }
        };

        // Cost Head Management
        const openCostHeadModal = async () => {
            await loadCostHeads();
            if (costHeadModal) {
                costHeadModal.show();
            } else {
                console.error('Cost head modal not initialized');
            }
        };

        const loadCostHeads = async () => {
            try {
                const response = await axios.get('/api/cost-heads');
                costHeads.value = response.data || [];
            } catch (error) {
                console.error('Error loading cost heads:', error);
            }
        };

        const loadGstPlans = async () => {
            try {
                const response = await axios.get('/api/gst-plans/active');
                gstPlans.value = response.data || [];
            } catch (error) {
                console.error('Error loading GST plans:', error);
            }
        };

        const openAddCostHeadForm = () => {
            costHeadEditMode.value = false;
            costHeadForm.value = {
                cost_head_code: '',
                cost_head_name: '',
                cost_head_type: 'opd_services',
                description: '',
                is_active: true,
            };
            showCostHeadForm.value = true;
        };

        const editCostHead = (costHead) => {
            costHeadEditMode.value = true;
            costHeadForm.value = { ...costHead };
            showCostHeadForm.value = true;
        };

        const saveCostHead = async () => {
            try {
                if (costHeadEditMode.value) {
                    await axios.put(`/api/cost-heads/${costHeadForm.value.cost_head_id}`, costHeadForm.value);
                } else {
                    await axios.post('/api/cost-heads', costHeadForm.value);
                }

                showCostHeadForm.value = false;
                await loadCostHeads();
                await loadMasterData();
            } catch (error) {
                console.error('Error saving cost head:', error);
                alert('Failed to save cost head: ' + (error.response?.data?.message || error.message));
            }
        };

        const deleteCostHead = async (costHead) => {
            if (!confirm(`Are you sure you want to delete "${costHead.cost_head_name}"?`)) return;

            try {
                await axios.delete(`/api/cost-heads/${costHead.cost_head_id}`);
                await loadCostHeads();
                await loadMasterData();
            } catch (error) {
                console.error('Error deleting cost head:', error);
                alert('Failed to delete cost head: ' + (error.response?.data?.message || error.message));
            }
        };

        return {
            loading,
            saving,
            services,
            insuranceCompanies,
            costHeads,
            gstPlans,
            roomsAndBeds,
            allRooms,
            allBeds,
            filters,
            editMode,
            form,
            roomPrices,
            bedPrices,
            selectedCostHeadType,
            serviceModalRef,
            costHeadModalRef,
            setPricesModalRef,
            priceDetailsModalRef,
            showCostHeadForm,
            costHeadEditMode,
            costHeadForm,
            selectedService,
            selectedWardId,
            wards,
            wardRooms,
            selectedRoomId,
            selectedRoom,
            tempPrices,
            savingPrices,
            priceDetailsService,
            roomPricesList,
            bedPricesList,
            loadServices,
            clearFilters,
            openAddModal,
            editService,
            onCostHeadChange,
            saveService,
            deleteService,
            showPriceDetails,
            openSetPricesModal,
            openSetPricesModalFromDetails,
            loadWardRooms,
            selectRoom,
            savePrices,
            openCostHeadModal,
            openAddCostHeadForm,
            editCostHead,
            saveCostHead,
            deleteCostHead,
        };
    },
};
</script>

<style scoped>
.table th {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table td input {
    max-width: 100px;
}

.list-group-item.active {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item.active:hover {
    background-color: #0b5ed7;
}

.list-group {
    max-height: 500px;
    overflow-y: auto;
}
</style>
