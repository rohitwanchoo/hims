<template>
    <div>
        <div class="row">
            <!-- Left Sidebar Menu -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Masters</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a
                            href="#"
                            class="list-group-item list-group-item-action"
                            :class="{ 'active': activeMaster === 'drug' }"
                            @click.prevent="activeMaster = 'drug'"
                        >
                            Drug & Drug Type Master
                        </a>
                        <a
                            href="#"
                            class="list-group-item list-group-item-action"
                            :class="{ 'active': activeMaster === 'dose' }"
                            @click.prevent="activeMaster = 'dose'"
                        >
                            Dose Master
                        </a>
                        <a
                            href="#"
                            class="list-group-item list-group-item-action"
                            :class="{ 'active': activeMaster === 'dosetime' }"
                            @click.prevent="activeMaster = 'dosetime'"
                        >
                            Dose Time Master
                        </a>
                        <a
                            href="#"
                            class="list-group-item list-group-item-action"
                            :class="{ 'active': activeMaster === 'disease' }"
                            @click.prevent="activeMaster = 'disease'"
                        >
                            Disease Master
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Content Area -->
            <div class="col-md-9">
                <!-- Drug & Drug Type Master -->
                <div v-if="activeMaster === 'drug'" class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Drug Master</h5>
                    </div>
                    <div class="card-body">
                        <DrugMaster />
                    </div>
                </div>

                <!-- Dose Master -->
                <div v-if="activeMaster === 'dose'" class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Dosage Master</h5>
                    </div>
                    <div class="card-body">
                        <DoseMaster />
                    </div>
                </div>

                <!-- Dose Time Master -->
                <div v-if="activeMaster === 'dosetime'" class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Dose Time Master</h5>
                    </div>
                    <div class="card-body">
                        <DoseTimeMaster />
                    </div>
                </div>

                <!-- Disease Master -->
                <div v-if="activeMaster === 'disease'" class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Disease Master</h5>
                    </div>
                    <div class="card-body">
                        <DiseaseMaster />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import DrugMaster from './DrugMaster.vue';
import DoseMaster from './DoseMaster.vue';
import DoseTimeMaster from './DoseTimeMaster.vue';
import DiseaseMaster from './DiseaseMaster.vue';

const activeMaster = ref('drug');
</script>

<style scoped>
.list-group-item.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.list-group-item-action:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.card-header {
    font-weight: 600;
}
</style>
