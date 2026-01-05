<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title">
              <i class="bi bi-person-plus me-2"></i>
              Add New Patient
            </h5>
          </div>
          <div class="card-body">
            <form @submit.prevent="submitForm">
              <div class="row">
                <!-- First Name -->
                <div class="col-md-6 mb-3">
                  <label for="first_name" class="form-label">First Name *</label>
                  <input 
                    type="text" 
                    id="first_name" 
                    v-model="form.first_name" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.first_name }"
                    required
                  >
                  <div v-if="errors.first_name" class="invalid-feedback">
                    {{ errors.first_name[0] }}
                  </div>
                </div>

                <!-- Last Name -->
                <div class="col-md-6 mb-3">
                  <label for="last_name" class="form-label">Last Name *</label>
                  <input 
                    type="text" 
                    id="last_name" 
                    v-model="form.last_name" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.last_name }"
                    required
                  >
                  <div v-if="errors.last_name" class="invalid-feedback">
                    {{ errors.last_name[0] }}
                  </div>
                </div>

                <!-- Date of Birth -->
                <div class="col-md-6 mb-3">
                  <label for="date_of_birth" class="form-label">Date of Birth *</label>
                  <input 
                    type="date" 
                    id="date_of_birth" 
                    v-model="form.date_of_birth" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.date_of_birth }"
                    required
                  >
                  <div v-if="errors.date_of_birth" class="invalid-feedback">
                    {{ errors.date_of_birth[0] }}
                  </div>
                </div>

                <!-- Gender -->
                <div class="col-md-6 mb-3">
                  <label for="gender" class="form-label">Gender *</label>
                  <select 
                    id="gender" 
                    v-model="form.gender" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.gender }"
                    required
                  >
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                  </select>
                  <div v-if="errors.gender" class="invalid-feedback">
                    {{ errors.gender[0] }}
                  </div>
                </div>

                <!-- Phone -->
                <div class="col-md-6 mb-3">
                  <label for="phone" class="form-label">Phone</label>
                  <input 
                    type="tel" 
                    id="phone" 
                    v-model="form.phone" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.phone }"
                  >
                  <div v-if="errors.phone" class="invalid-feedback">
                    {{ errors.phone[0] }}
                  </div>
                </div>

                <!-- Patient Type - NEW FIELD -->
                <div class="col-md-6 mb-3">
                  <label for="patient_type" class="form-label">Patient Type</label>
                  <select 
                    id="patient_type" 
                    v-model="form.patient_type" 
                    class="form-select"
                    :class="{ 'is-invalid': errors.patient_type }"
                  >
                    <option value="">Select Patient Type</option>
                    <option value="Staff">Staff</option>
                    <option value="Doctor">Doctor</option>
                  </select>
                  <div v-if="errors.patient_type" class="invalid-feedback">
                    {{ errors.patient_type[0] }}
                  </div>
                  <div class="form-text">Optional - Select if patient is staff or doctor</div>
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input 
                    type="email" 
                    id="email" 
                    v-model="form.email" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                  >
                  <div v-if="errors.email" class="invalid-feedback">
                    {{ errors.email[0] }}
                  </div>
                </div>

                <!-- Blood Group -->
                <div class="col-md-6 mb-3">
                  <label for="blood_group" class="form-label">Blood Group</label>
                  <select 
                    id="blood_group" 
                    v-model="form.blood_group" 
                    class="form-select"
                  >
                    <option value="">Select Blood Group</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                  </select>
                </div>

                <!-- Address -->
                <div class="col-md-12 mb-3">
                  <label for="address" class="form-label">Address</label>
                  <textarea 
                    id="address" 
                    v-model="form.address" 
                    class="form-control"
                    rows="3"
                  ></textarea>
                </div>

                <!-- Emergency Contact -->
                <div class="col-md-6 mb-3">
                  <label for="emergency_contact" class="form-label">Emergency Contact</label>
                  <input 
                    type="text" 
                    id="emergency_contact" 
                    v-model="form.emergency_contact" 
                    class="form-control"
                  >
                </div>

                <!-- Emergency Phone -->
                <div class="col-md-6 mb-3">
                  <label for="emergency_phone" class="form-label">Emergency Phone</label>
                  <input 
                    type="tel" 
                    id="emergency_phone" 
                    v-model="form.emergency_phone" 
                    class="form-control"
                  >
                </div>

                <!-- Allergies -->
                <div class="col-md-6 mb-3">
                  <label for="allergies" class="form-label">Allergies</label>
                  <textarea 
                    id="allergies" 
                    v-model="form.allergies" 
                    class="form-control"
                    rows="3"
                  ></textarea>
                </div>

                <!-- Medical History -->
                <div class="col-md-6 mb-3">
                  <label for="medical_history" class="form-label">Medical History</label>
                  <textarea 
                    id="medical_history" 
                    v-model="form.medical_history" 
                    class="form-control"
                    rows="3"
                  ></textarea>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-12">
                  <button type="submit" class="btn btn-primary me-2" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                    Create Patient
                  </button>
                  <router-link to="/patients" class="btn btn-secondary">Cancel</router-link>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const loading = ref(false)
const errors = ref({})

const form = reactive({
  first_name: '',
  last_name: '',
  date_of_birth: '',
  gender: '',
  phone: '',
  patient_type: '',
  email: '',
  address: '',
  emergency_contact: '',
  emergency_phone: '',
  blood_group: '',
  allergies: '',
  medical_history: ''
})

const submitForm = async () => {
  try {
    loading.value = true
    errors.value = {}

    const response = await axios.post('/api/patients', form)
    
    if (response.data.success) {
      router.push('/patients')
    }
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    loading.value = false
  }
}
</script>