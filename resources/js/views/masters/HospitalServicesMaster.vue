<template>
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Hospital Services</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-info btn-sm" @click="openCashlessPriceListModal">
                        <i class="bi bi-cash-coin"></i> Cashless PriceList
                    </button>
                    <button class="btn btn-primary btn-sm" @click="openAddModal">
                        <i class="bi bi-plus-circle"></i> Add Service
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Cost Head</label>
                        <select class="form-select form-select-sm" v-model="filters.cost_head_id" @change="loadServices">
                            <option value="">All</option>
                            <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                {{ ch.cost_head_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.from_date" @change="loadServices">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.to_date" @change="loadServices">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search Services</label>
                        <input type="text" class="form-control form-control-sm" v-model="filters.search" @input="loadServices" placeholder="Search by service name...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
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
                                <th style="min-width: 120px;" class="text-end">Base Price</th>
                                <th style="min-width: 150px;" class="text-center">Room/Bed Prices</th>
                                <th style="min-width: 150px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="services.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No services found. Click "Add Service" to create one.
                                </td>
                            </tr>
                            <tr v-else v-for="(service, index) in services" :key="service.hospital_service_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ service.service_name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ service.cost_head_name }}</span>
                                </td>
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
                                <div class="col-md-12 mb-3">
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
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> To set cashless prices for insurance companies, use the "Cashless PriceList" button after creating the service.
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

        <!-- Cashless PriceList Bulk Update Modal -->
        <div class="modal fade" ref="cashlessPriceListModalRef" tabindex="-1">
            <div class="modal-dialog modal-fullscreen-lg-down" style="max-width: 95vw; margin-top: 100px; margin-bottom: 20px;">
                <div class="modal-content" style="max-height: calc(100vh - 120px); display: flex; flex-direction: column;">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-cash-coin"></i> Cashless PriceList - Bulk Update
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto; flex: 1;">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Class Name *</label>
                                <select class="form-select" v-model="cashlessFilters.insurance_id" @change="onCashlessFilterChange">
                                    <option value="">-- Select Class Name --</option>
                                    <option value="private">General</option>
                                    <option v-for="ins in insuranceCompanies" :key="ins.insurance_id" :value="ins.insurance_id">
                                        {{ ins.company_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cost Head</label>
                                <select class="form-select" v-model="cashlessFilters.cost_head_id" @change="onCashlessFilterChange">
                                    <option value="">All Cost Heads</option>
                                    <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                        {{ ch.cost_head_name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Date Range Filters -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" v-model="cashlessFilters.from_date" @change="loadCashlessServices">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" v-model="cashlessFilters.to_date" @change="loadCashlessServices">
                            </div>
                        </div>

                        <!-- Services Table -->
                        <div v-if="cashlessFilters.insurance_id">
                            <div v-if="loadingCashlessServices" class="text-center py-4">
                                <div class="spinner-border" role="status"></div>
                                <div>Loading services...</div>
                            </div>
                            <div v-else-if="cashlessServices.length === 0" class="alert alert-info">
                                No services found for the selected filters.
                            </div>
                            <div v-else class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                                <div class="mb-2 d-flex justify-content-end">
                                    <button
                                        class="btn btn-sm btn-outline-primary"
                                        @click="copyAllBasePricesToCashless"
                                        type="button"
                                        title="Copy all base prices to cashless prices"
                                    >
                                        <i class="bi bi-files"></i> Copy All Base Prices to Cashless
                                    </button>
                                </div>
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th style="min-width: 200px;">Service Name</th>
                                            <th style="min-width: 120px;">Cost Head</th>
                                            <th style="min-width: 100px;" class="text-end">Base Price</th>
                                            <th style="min-width: 100px;" class="text-end">Day Emg.</th>
                                            <th style="min-width: 100px;" class="text-end">Night Emg.</th>
                                            <th style="width: 60px;" class="text-center">Copy</th>
                                            <th style="min-width: 120px;" class="text-end bg-info-subtle">CL Rate</th>
                                            <th style="min-width: 120px;" class="text-end bg-info-subtle">CL Day Emg.</th>
                                            <th style="min-width: 120px;" class="text-end bg-info-subtle">CL Night Emg.</th>
                                            <th style="min-width: 120px;" class="text-center bg-success-subtle" v-if="selectedCashlessCostHeadType === 'ipd_services'">Ward Prices</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template v-for="(service, index) in cashlessServices" :key="service.hospital_service_id">
                                            <tr>
                                                <td>{{ index + 1 }}</td>
                                                <td>{{ service.service_name }}</td>
                                                <td>
                                                    <span class="badge bg-info">{{ service.cost_head_name }}</span>
                                                </td>
                                                <td class="text-end text-muted">₹{{ parseFloat(service.base_price).toFixed(2) }}</td>
                                                <td class="text-end text-muted">₹{{ parseFloat(service.day_emergency_rate || 0).toFixed(2) }}</td>
                                                <td class="text-end text-muted">₹{{ parseFloat(service.night_emergency_rate || 0).toFixed(2) }}</td>
                                                <td class="text-center">
                                                    <button
                                                        class="btn btn-sm btn-outline-secondary"
                                                        @click="copyBasePriceToCashless(service)"
                                                        type="button"
                                                        title="Copy base prices to cashless"
                                                    >
                                                        <i class="bi bi-arrow-right"></i>
                                                    </button>
                                                </td>
                                                <td class="bg-info-subtle">
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-end"
                                                        v-model="cashlessUpdates[service.hospital_service_id].cl_rate"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                                <td class="bg-info-subtle">
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-end"
                                                        v-model="cashlessUpdates[service.hospital_service_id].cl_day_emergency_rate"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                                <td class="bg-info-subtle">
                                                    <input
                                                        type="number"
                                                        class="form-control form-control-sm text-end"
                                                        v-model="cashlessUpdates[service.hospital_service_id].cl_night_emergency_rate"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                                <td class="text-center bg-success-subtle" v-if="selectedCashlessCostHeadType === 'ipd_services'">
                                                    <button
                                                        class="btn btn-sm btn-outline-success"
                                                        @click="toggleWardPrices(service.hospital_service_id)"
                                                        type="button"
                                                    >
                                                        <i class="bi" :class="expandedServiceId === service.hospital_service_id ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                                        Ward Prices
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Expanded Ward Prices Row -->
                                            <tr v-if="expandedServiceId === service.hospital_service_id && selectedCashlessCostHeadType === 'ipd_services'">
                                                <td colspan="11" class="p-3 bg-light">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-header bg-success text-white">
                                                            <h6 class="mb-0">
                                                                <i class="bi bi-hospital"></i> Ward Prices for {{ service.service_name }}
                                                            </h6>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-4 mb-2" v-for="ward in wards" :key="ward.ward_id">
                                                                    <label class="form-label small mb-1">
                                                                        <strong>{{ ward.ward_name }}</strong>
                                                                    </label>
                                                                    <div class="input-group input-group-sm">
                                                                        <span class="input-group-text">₹</span>
                                                                        <input
                                                                            type="number"
                                                                            class="form-control"
                                                                            v-model="cashlessUpdates[service.hospital_service_id].ward_prices[ward.ward_id]"
                                                                            step="0.01"
                                                                            min="0"
                                                                            placeholder="0.00"
                                                                        >
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info mt-3 mb-0">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong> Only modified services will be updated. Leave fields blank to keep existing values.
                            </div>
                        </div>
                        <div v-else class="alert alert-warning">
                            Please select an Insurance Company to view services.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-info" @click="openPriceHistoryModal">
                            <i class="bi bi-clock-history"></i> Price History
                        </button>
                        <div class="flex-grow-1"></div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="saveCashlessUpdates"
                            :disabled="savingCashless || !cashlessFilters.insurance_id || cashlessServices.length === 0"
                        >
                            <span v-if="savingCashless" class="spinner-border spinner-border-sm me-1"></span>
                            {{ savingCashless ? 'Updating...' : 'Update Cashless Prices' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Price History Modal -->
        <div class="modal fade" ref="priceHistoryModalRef" tabindex="-1">
            <div class="modal-dialog modal-fullscreen-lg-down" style="max-width: 95vw; margin-top: 100px; margin-bottom: 20px;">
                <div class="modal-content" style="max-height: calc(100vh - 120px); display: flex; flex-direction: column;">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-clock-history"></i> Cashless Price Change History
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto; flex: 1;">
                        <!-- Filters -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Service Name</label>
                                <input type="text" class="form-control form-control-sm" v-model="historyFilters.service_name" @input="loadPriceHistory" placeholder="Search service...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Class Name</label>
                                <input type="text" class="form-control form-control-sm" v-model="historyFilters.insurance_company_name" @input="loadPriceHistory" placeholder="Search class...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="historyFilters.from_date" @change="loadPriceHistory">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="historyFilters.to_date" @change="loadPriceHistory">
                            </div>
                        </div>

                        <!-- History Table -->
                        <div v-if="loadingHistory" class="text-center py-4">
                            <div class="spinner-border" role="status"></div>
                            <div>Loading history...</div>
                        </div>
                        <div v-else-if="priceHistory.length === 0" class="alert alert-info">
                            No price change history found.
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="min-width: 200px;">Service Name</th>
                                        <th style="min-width: 120px;">Class Name</th>
                                        <th style="min-width: 100px;" class="text-end">Old CL Rate</th>
                                        <th style="min-width: 100px;" class="text-end">Old Day Emg</th>
                                        <th style="min-width: 100px;" class="text-end">Old Night Emg</th>
                                        <th style="width: 50px;" class="text-center"><i class="bi bi-arrow-right"></i></th>
                                        <th style="min-width: 100px;" class="text-end bg-success-subtle">New CL Rate</th>
                                        <th style="min-width: 100px;" class="text-end bg-success-subtle">New Day Emg</th>
                                        <th style="min-width: 100px;" class="text-end bg-success-subtle">New Night Emg</th>
                                        <th style="min-width: 120px;">Updated By</th>
                                        <th style="min-width: 150px;">Updated At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(history, index) in priceHistory" :key="history.history_id">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ history.service_name }}</td>
                                        <td>{{ history.insurance_company_name || 'N/A' }}</td>
                                        <td class="text-end">₹{{ parseFloat(history.old_cl_rate).toFixed(2) }}</td>
                                        <td class="text-end">₹{{ parseFloat(history.old_cl_day_emergency_rate).toFixed(2) }}</td>
                                        <td class="text-end">₹{{ parseFloat(history.old_cl_night_emergency_rate).toFixed(2) }}</td>
                                        <td class="text-center"><i class="bi bi-arrow-right text-primary"></i></td>
                                        <td class="text-end bg-success-subtle"><strong>₹{{ parseFloat(history.new_cl_rate).toFixed(2) }}</strong></td>
                                        <td class="text-end bg-success-subtle"><strong>₹{{ parseFloat(history.new_cl_day_emergency_rate).toFixed(2) }}</strong></td>
                                        <td class="text-end bg-success-subtle"><strong>₹{{ parseFloat(history.new_cl_night_emergency_rate).toFixed(2) }}</strong></td>
                                        <td>{{ history.updated_by_name || 'Unknown' }}</td>
                                        <td>{{ formatDateTime(history.updated_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-between align-items-center mt-3" v-if="historyPagination.last_page > 1">
                                <div>
                                    Showing {{ historyPagination.from }} to {{ historyPagination.to }} of {{ historyPagination.total }} records
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li class="page-item" :class="{ disabled: historyPagination.current_page === 1 }">
                                            <button class="page-link" @click="changePage(historyPagination.current_page - 1)">Previous</button>
                                        </li>
                                        <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: page === historyPagination.current_page }">
                                            <button class="page-link" @click="changePage(page)">{{ page }}</button>
                                        </li>
                                        <li class="page-item" :class="{ disabled: historyPagination.current_page === historyPagination.last_page }">
                                            <button class="page-link" @click="changePage(historyPagination.current_page + 1)">Next</button>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
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
            cost_head_id: '',
            search: '',
            from_date: '',
            to_date: '',
        });

        const editMode = ref(false);
        const form = ref({
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
        let cashlessPriceListModal = null;
        let priceHistoryModalInstance = null;
        const serviceModalRef = ref(null);
        const costHeadModalRef = ref(null);
        const setPricesModalRef = ref(null);
        const priceDetailsModalRef = ref(null);
        const cashlessPriceListModalRef = ref(null);
        const priceHistoryModalRef = ref(null);

        // Cashless PriceList Bulk Update
        const cashlessFilters = ref({
            insurance_id: '',
            cost_head_id: '',
            from_date: '',
            to_date: '',
        });
        const cashlessServices = ref([]);
        const loadingCashlessServices = ref(false);
        const cashlessUpdates = ref({});
        const savingCashless = ref(false);
        const selectedCashlessCostHeadType = ref('');
        const expandedServiceId = ref(null);

        // Price History
        const historyFilters = ref({
            service_name: '',
            insurance_company_name: '',
            from_date: '',
            to_date: '',
        });
        const priceHistory = ref([]);
        const loadingHistory = ref(false);
        const historyPagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

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
            if (cashlessPriceListModalRef.value) {
                cashlessPriceListModal = new Modal(cashlessPriceListModalRef.value);
            }
            if (priceHistoryModalRef.value) {
                priceHistoryModalInstance = new Modal(priceHistoryModalRef.value);
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
                if (filters.value.cost_head_id) params.append('cost_head_id', filters.value.cost_head_id);
                if (filters.value.search) params.append('search', filters.value.search);
                if (filters.value.from_date) params.append('from_date', filters.value.from_date);
                if (filters.value.to_date) params.append('to_date', filters.value.to_date);

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
                cost_head_id: '',
                search: '',
                from_date: '',
                to_date: '',
            };
            loadServices();
        };

        const openAddModal = () => {
            editMode.value = false;
            form.value = {
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

        // Cashless PriceList Functions
        const openCashlessPriceListModal = () => {
            cashlessFilters.value = {
                insurance_id: '',
                cost_head_id: '',
                from_date: '',
                to_date: '',
            };
            cashlessServices.value = [];
            cashlessUpdates.value = {};
            selectedCashlessCostHeadType.value = '';
            expandedServiceId.value = null;

            if (cashlessPriceListModal) {
                cashlessPriceListModal.show();
            } else {
                console.error('Cashless price list modal not initialized');
            }
        };

        const toggleWardPrices = (serviceId) => {
            if (expandedServiceId.value === serviceId) {
                expandedServiceId.value = null;
            } else {
                expandedServiceId.value = serviceId;
            }
        };

        const copyBasePriceToCashless = (service) => {
            if (cashlessUpdates.value[service.hospital_service_id]) {
                cashlessUpdates.value[service.hospital_service_id].cl_rate = parseFloat(service.base_price) || 0;
                cashlessUpdates.value[service.hospital_service_id].cl_day_emergency_rate = parseFloat(service.day_emergency_rate) || 0;
                cashlessUpdates.value[service.hospital_service_id].cl_night_emergency_rate = parseFloat(service.night_emergency_rate) || 0;
            }
        };

        const copyAllBasePricesToCashless = () => {
            cashlessServices.value.forEach(service => {
                copyBasePriceToCashless(service);
            });
        };

        const onCashlessFilterChange = () => {
            // Detect cost head type
            if (cashlessFilters.value.cost_head_id) {
                const selectedCostHead = costHeads.value.find(ch => ch.cost_head_id == cashlessFilters.value.cost_head_id);
                selectedCashlessCostHeadType.value = selectedCostHead ? selectedCostHead.cost_head_type : '';
            } else {
                selectedCashlessCostHeadType.value = '';
            }

            // Load services
            loadCashlessServices();
        };

        const loadCashlessServices = async () => {
            if (!cashlessFilters.value.insurance_id) {
                cashlessServices.value = [];
                cashlessUpdates.value = {};
                return;
            }

            loadingCashlessServices.value = true;
            try {
                const params = new URLSearchParams();
                params.append('insurance_id', cashlessFilters.value.insurance_id);

                if (cashlessFilters.value.cost_head_id) {
                    params.append('cost_head_id', cashlessFilters.value.cost_head_id);
                }

                if (cashlessFilters.value.from_date) {
                    params.append('from_date', cashlessFilters.value.from_date);
                }

                if (cashlessFilters.value.to_date) {
                    params.append('to_date', cashlessFilters.value.to_date);
                }

                const response = await axios.get('/api/hospital-services?' + params.toString());
                cashlessServices.value = response.data || [];

                // Initialize cashlessUpdates with existing values
                cashlessUpdates.value = {};
                cashlessServices.value.forEach(service => {
                    // Initialize ward_prices object for each ward
                    const wardPrices = {};
                    wards.value.forEach(ward => {
                        wardPrices[ward.ward_id] = 0;
                    });

                    cashlessUpdates.value[service.hospital_service_id] = {
                        cl_rate: service.cl_rate || 0,
                        cl_day_emergency_rate: service.cl_day_emergency_rate || 0,
                        cl_night_emergency_rate: service.cl_night_emergency_rate || 0,
                        ward_prices: wardPrices,
                        // Store original values for comparison
                        _original: {
                            cl_rate: service.cl_rate || 0,
                            cl_day_emergency_rate: service.cl_day_emergency_rate || 0,
                            cl_night_emergency_rate: service.cl_night_emergency_rate || 0,
                        }
                    };
                });
            } catch (error) {
                console.error('Error loading cashless services:', error);
                alert('Failed to load services: ' + (error.response?.data?.message || error.message));
            } finally {
                loadingCashlessServices.value = false;
            }
        };

        const saveCashlessUpdates = async () => {
            if (!cashlessFilters.value.insurance_id) {
                alert('Please select an insurance company');
                return;
            }

            savingCashless.value = true;
            try {
                // Prepare bulk update payload with global from_date and to_date
                const updates = [];
                Object.keys(cashlessUpdates.value).forEach(serviceId => {
                    const update = cashlessUpdates.value[serviceId];
                    const original = update._original || {};

                    // Check if values have changed from original
                    const clRateChanged = parseFloat(update.cl_rate) !== parseFloat(original.cl_rate);
                    const clDayChanged = parseFloat(update.cl_day_emergency_rate) !== parseFloat(original.cl_day_emergency_rate);
                    const clNightChanged = parseFloat(update.cl_night_emergency_rate) !== parseFloat(original.cl_night_emergency_rate);

                    // Check if any ward prices were set
                    let hasWardPrices = false;
                    if (update.ward_prices && selectedCashlessCostHeadType.value === 'ipd_services') {
                        hasWardPrices = Object.values(update.ward_prices).some(price => parseFloat(price) > 0);
                    }

                    // Only include this service if something changed or ward prices were set
                    if (clRateChanged || clDayChanged || clNightChanged || hasWardPrices) {
                        // Prepare ward prices array
                        const wardPrices = [];
                        if (update.ward_prices && selectedCashlessCostHeadType.value === 'ipd_services') {
                            Object.keys(update.ward_prices).forEach(wardId => {
                                const price = parseFloat(update.ward_prices[wardId]);
                                if (price > 0) {
                                    wardPrices.push({
                                        ward_id: wardId,
                                        price: price
                                    });
                                }
                            });
                        }

                        updates.push({
                            hospital_service_id: serviceId,
                            cashless_pricelist: cashlessFilters.value.insurance_id,
                            cl_rate: parseFloat(update.cl_rate) || 0,
                            cl_day_emergency_rate: parseFloat(update.cl_day_emergency_rate) || 0,
                            cl_night_emergency_rate: parseFloat(update.cl_night_emergency_rate) || 0,
                            from_date: cashlessFilters.value.from_date || null,
                            to_date: cashlessFilters.value.to_date || null,
                            ward_prices: wardPrices,
                        });
                    }
                });

                // Check if any services were modified
                if (updates.length === 0) {
                    alert('No changes detected. Please modify at least one service before updating.');
                    savingCashless.value = false;
                    return;
                }

                // Send bulk update request
                await axios.post('/api/hospital-services/bulk-update-cashless', {
                    updates: updates
                });

                alert(`Successfully updated ${updates.length} service(s)!`);

                if (cashlessPriceListModal) {
                    cashlessPriceListModal.hide();
                }

                await loadServices();
            } catch (error) {
                console.error('Error saving cashless updates:', error);
                alert('Failed to update cashless prices: ' + (error.response?.data?.message || error.message));
            } finally {
                savingCashless.value = false;
            }
        };

        // Price History Functions
        const openPriceHistoryModal = async () => {
            historyFilters.value = {
                service_name: '',
                insurance_company_name: '',
                from_date: '',
                to_date: '',
            };
            await loadPriceHistory();
            if (priceHistoryModalInstance) {
                priceHistoryModalInstance.show();
            } else {
                console.error('Price history modal not initialized');
            }
        };

        const loadPriceHistory = async (page = 1) => {
            loadingHistory.value = true;
            try {
                const params = new URLSearchParams();
                if (historyFilters.value.service_name) {
                    params.append('service_name', historyFilters.value.service_name);
                }
                if (historyFilters.value.insurance_company_name) {
                    params.append('insurance_company_name', historyFilters.value.insurance_company_name);
                }
                if (historyFilters.value.from_date) {
                    params.append('from_date', historyFilters.value.from_date);
                }
                if (historyFilters.value.to_date) {
                    params.append('to_date', historyFilters.value.to_date);
                }
                params.append('page', page);
                params.append('per_page', 50);

                const response = await axios.get('/api/cashless-price-history?' + params.toString());
                priceHistory.value = response.data.data || [];
                historyPagination.value = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    from: response.data.from || 0,
                    to: response.data.to || 0,
                    total: response.data.total || 0,
                };
            } catch (error) {
                console.error('Error loading price history:', error);
                alert('Failed to load price history: ' + (error.response?.data?.message || error.message));
            } finally {
                loadingHistory.value = false;
            }
        };

        const changePage = (page) => {
            if (page >= 1 && page <= historyPagination.value.last_page) {
                loadPriceHistory(page);
            }
        };

        const visiblePages = computed(() => {
            const current = historyPagination.value.current_page;
            const last = historyPagination.value.last_page;
            const pages = [];

            // Show max 5 pages
            let start = Math.max(1, current - 2);
            let end = Math.min(last, start + 4);
            start = Math.max(1, end - 4);

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        });

        const formatDateTime = (dateTime) => {
            if (!dateTime) return 'N/A';
            const date = new Date(dateTime);
            return date.toLocaleString('en-IN', {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
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
            cashlessPriceListModalRef,
            priceHistoryModalRef,
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
            cashlessFilters,
            cashlessServices,
            loadingCashlessServices,
            cashlessUpdates,
            savingCashless,
            selectedCashlessCostHeadType,
            expandedServiceId,
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
            openCashlessPriceListModal,
            onCashlessFilterChange,
            loadCashlessServices,
            saveCashlessUpdates,
            toggleWardPrices,
            copyBasePriceToCashless,
            copyAllBasePricesToCashless,
            historyFilters,
            priceHistory,
            loadingHistory,
            historyPagination,
            openPriceHistoryModal,
            loadPriceHistory,
            changePage,
            visiblePages,
            formatDateTime,
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
