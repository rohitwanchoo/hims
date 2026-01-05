<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Patient Details</h4>
            <router-link :to="`/patients/${route.params.id}/edit`" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </router-link>
        </div>
        <div class="card" v-if="patient">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Code:</strong> {{ patient.pcd }}</p>
                        <p><strong>Name:</strong> {{ patient.patient_name }}</p>
                        <p><strong>Age/Gender:</strong> {{ patient.age }} {{ patient.age_unit }} / {{ patient.gender }}</p>
                        <p><strong>Mobile:</strong> {{ patient.mobile || '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ patient.email || '-' }}</p>
                        <p><strong>Blood Group:</strong> {{ patient.blood_group || '-' }}</p>
                        <p><strong>Address:</strong> {{ patient.address || '-' }}</p>
                        <p><strong>City:</strong> {{ patient.city || '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const patient = ref(null);

onMounted(async () => {
    const response = await axios.get(`/api/patients/${route.params.id}`);
    patient.value = response.data;
});
</script>
