<template>
    <div>
        <p class="text-muted small mb-3">
            To Edit, Click on Name & Press INSERT Key.<br>
            To Delete, Click on Name & Press DELETE Key.
        </p>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Drug Type</label>
                <select v-model="selectedDrugType" class="form-select">
                    <option value="">ALPH</option>
                    <option v-for="type in drugTypes" :key="type.id" :value="type.id">
                        {{ type.name }}
                    </option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Language</label>
                <select v-model="selectedLanguage" class="form-select">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">New Drug Type</label>
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        v-model="newDrugType"
                        placeholder="Enter new drug type"
                    />
                    <button class="btn btn-primary" @click="addDrugType">
                        Add
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Drug Name</label>
                <input type="text" class="form-control" v-model="form.drug_name" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Dose Time</label>
                <select class="form-select" v-model="form.dose_time">
                    <option value="">D</option>
                    <option value="morning">Morning</option>
                    <option value="afternoon">Afternoon</option>
                    <option value="evening">Evening</option>
                    <option value="night">Night</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Days</label>
                <input type="text" class="form-control" v-model="form.days" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" class="form-control" v-model="form.quantity" />
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" @click="saveDrug">
                    Save
                </button>
            </div>
        </div>

        <!-- Drug List -->
        <div class="border rounded p-3" style="height: 400px; overflow-y: auto;">
            <div class="mb-3">
                <label class="form-label fw-bold">Description</label>
            </div>
            <div
                v-for="drug in filteredDrugs"
                :key="drug.id"
                class="drug-item py-1 px-2 mb-1 cursor-pointer"
                :class="{ 'bg-primary text-white': selectedDrug?.id === drug.id }"
                @click="selectDrug(drug)"
                @dblclick="editDrug(drug)"
            >
                {{ drug.name }}
            </div>
            <div v-if="filteredDrugs.length === 0" class="text-center text-muted py-4">
                No drugs found
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const selectedDrugType = ref('');
const selectedLanguage = ref('english');
const newDrugType = ref('');
const selectedDrug = ref(null);

const form = ref({
    drug_name: '',
    dose_time: '',
    days: '',
    quantity: ''
});

const drugTypes = ref([
    { id: 1, name: 'TABLET' },
    { id: 2, name: 'SYRUP' },
    { id: 3, name: 'INJECTION' },
    { id: 4, name: 'CAPSULE' },
    { id: 5, name: 'OINTMENT' }
]);

const drugs = ref([
    { id: 1, name: '4 BUTYL RESORCINOL ARBUTIN MILK PROTEIN ACCELERATOR', type: 'ALPH' },
    { id: 2, name: 'ALPHA-GLUCAN ZINC PCA', type: 'ALPH' },
    { id: 3, name: 'BANDAGE', type: 'ALPH' },
    { id: 4, name: 'BENZOYL PEROXIDE', type: 'ALPH' },
    { id: 5, name: 'BODY WASH', type: 'ALPH' },
    { id: 6, name: 'BOOSTER', type: 'ALPH' },
    { id: 7, name: 'BOTTLE', type: 'ALPH' },
    { id: 8, name: 'BRUSH', type: 'ALPH' },
    { id: 9, name: 'BUCCAL PASTE', type: 'ALPH' },
    { id: 10, name: 'CAP', type: 'ALPH' },
    { id: 11, name: 'CLEANSER', type: 'ALPH' },
    { id: 12, name: 'CLOTRIMAZOLE AND BECLOMETHASONE', type: 'ALPH' },
    { id: 13, name: 'CM', type: 'ALPH' },
    { id: 14, name: 'COLOUR', type: 'ALPH' },
    { id: 15, name: 'CONDITIONER', type: 'ALPH' },
    { id: 16, name: 'COTTON CREPE BANDAGE', type: 'ALPH' },
    { id: 17, name: 'CREAM', type: 'ALPH' },
    { id: 18, name: 'D-BIOTIN,N-ACETYL VIT MIN', type: 'ALPH' },
    { id: 19, name: 'DRY SYP', type: 'ALPH' },
    { id: 20, name: 'EMOLLIENT', type: 'ALPH' }
]);

const filteredDrugs = computed(() => {
    if (!selectedDrugType.value) {
        return drugs.value;
    }
    return drugs.value.filter(drug => drug.type === selectedDrugType.value);
});

const addDrugType = () => {
    if (newDrugType.value.trim()) {
        const newId = Math.max(...drugTypes.value.map(t => t.id), 0) + 1;
        drugTypes.value.push({
            id: newId,
            name: newDrugType.value.trim().toUpperCase()
        });
        newDrugType.value = '';
    }
};

const selectDrug = (drug) => {
    selectedDrug.value = drug;
};

const editDrug = (drug) => {
    form.value.drug_name = drug.name;
    selectedDrug.value = drug;
};

const saveDrug = () => {
    if (form.value.drug_name.trim()) {
        const newDrug = {
            id: Math.max(...drugs.value.map(d => d.id), 0) + 1,
            name: form.value.drug_name.trim(),
            type: selectedDrugType.value || 'ALPH'
        };
        drugs.value.push(newDrug);

        // Reset form
        form.value = {
            drug_name: '',
            dose_time: '',
            days: '',
            quantity: ''
        };
    }
};
</script>

<style scoped>
.drug-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.drug-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.cursor-pointer {
    cursor: pointer;
}
</style>
