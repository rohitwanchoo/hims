<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IcdCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            // Chapter 1: Certain infectious and parasitic diseases (A00-B99)
            ['icd_code' => 'A09', 'short_description' => 'Infectious gastroenteritis and colitis, unspecified', 'category' => 'A00-B99', 'chapter' => 'Infectious Diseases'],
            ['icd_code' => 'A15.0', 'short_description' => 'Tuberculosis of lung', 'category' => 'A00-B99', 'chapter' => 'Infectious Diseases'],
            ['icd_code' => 'A41.9', 'short_description' => 'Sepsis, unspecified organism', 'category' => 'A00-B99', 'chapter' => 'Infectious Diseases'],
            ['icd_code' => 'B34.9', 'short_description' => 'Viral infection, unspecified', 'category' => 'A00-B99', 'chapter' => 'Infectious Diseases'],
            ['icd_code' => 'B37.9', 'short_description' => 'Candidiasis, unspecified', 'category' => 'A00-B99', 'chapter' => 'Infectious Diseases'],

            // Chapter 2: Neoplasms (C00-D49)
            ['icd_code' => 'C34.9', 'short_description' => 'Malignant neoplasm of bronchus or lung, unspecified', 'category' => 'C00-D49', 'chapter' => 'Neoplasms'],
            ['icd_code' => 'C50.9', 'short_description' => 'Malignant neoplasm of breast, unspecified', 'category' => 'C00-D49', 'chapter' => 'Neoplasms'],
            ['icd_code' => 'C61', 'short_description' => 'Malignant neoplasm of prostate', 'category' => 'C00-D49', 'chapter' => 'Neoplasms'],
            ['icd_code' => 'D50.9', 'short_description' => 'Iron deficiency anemia, unspecified', 'category' => 'D50-D89', 'chapter' => 'Blood Diseases'],

            // Chapter 4: Endocrine, nutritional and metabolic diseases (E00-E89)
            ['icd_code' => 'E03.9', 'short_description' => 'Hypothyroidism, unspecified', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E05.9', 'short_description' => 'Thyrotoxicosis, unspecified', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E10.9', 'short_description' => 'Type 1 diabetes mellitus without complications', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E11.9', 'short_description' => 'Type 2 diabetes mellitus without complications', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E11.65', 'short_description' => 'Type 2 diabetes mellitus with hyperglycemia', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E66.9', 'short_description' => 'Obesity, unspecified', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E78.5', 'short_description' => 'Hyperlipidemia, unspecified', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],
            ['icd_code' => 'E87.6', 'short_description' => 'Hypokalemia', 'category' => 'E00-E89', 'chapter' => 'Endocrine/Metabolic'],

            // Chapter 5: Mental and behavioral disorders (F01-F99)
            ['icd_code' => 'F32.9', 'short_description' => 'Major depressive disorder, single episode, unspecified', 'category' => 'F01-F99', 'chapter' => 'Mental Disorders'],
            ['icd_code' => 'F41.1', 'short_description' => 'Generalized anxiety disorder', 'category' => 'F01-F99', 'chapter' => 'Mental Disorders'],
            ['icd_code' => 'F41.9', 'short_description' => 'Anxiety disorder, unspecified', 'category' => 'F01-F99', 'chapter' => 'Mental Disorders'],

            // Chapter 6: Diseases of the nervous system (G00-G99)
            ['icd_code' => 'G43.9', 'short_description' => 'Migraine, unspecified', 'category' => 'G00-G99', 'chapter' => 'Nervous System'],
            ['icd_code' => 'G40.9', 'short_description' => 'Epilepsy, unspecified', 'category' => 'G00-G99', 'chapter' => 'Nervous System'],
            ['icd_code' => 'G47.0', 'short_description' => 'Insomnia', 'category' => 'G00-G99', 'chapter' => 'Nervous System'],
            ['icd_code' => 'G62.9', 'short_description' => 'Polyneuropathy, unspecified', 'category' => 'G00-G99', 'chapter' => 'Nervous System'],

            // Chapter 7: Diseases of the eye (H00-H59)
            ['icd_code' => 'H10.9', 'short_description' => 'Conjunctivitis, unspecified', 'category' => 'H00-H59', 'chapter' => 'Eye Diseases'],
            ['icd_code' => 'H26.9', 'short_description' => 'Cataract, unspecified', 'category' => 'H00-H59', 'chapter' => 'Eye Diseases'],
            ['icd_code' => 'H40.9', 'short_description' => 'Glaucoma, unspecified', 'category' => 'H00-H59', 'chapter' => 'Eye Diseases'],

            // Chapter 8: Diseases of the ear (H60-H95)
            ['icd_code' => 'H66.9', 'short_description' => 'Otitis media, unspecified', 'category' => 'H60-H95', 'chapter' => 'Ear Diseases'],
            ['icd_code' => 'H91.9', 'short_description' => 'Hearing loss, unspecified', 'category' => 'H60-H95', 'chapter' => 'Ear Diseases'],

            // Chapter 9: Diseases of the circulatory system (I00-I99)
            ['icd_code' => 'I10', 'short_description' => 'Essential (primary) hypertension', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I20.9', 'short_description' => 'Angina pectoris, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I21.9', 'short_description' => 'Acute myocardial infarction, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I25.10', 'short_description' => 'Atherosclerotic heart disease of native coronary artery', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I48.91', 'short_description' => 'Atrial fibrillation, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I50.9', 'short_description' => 'Heart failure, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I63.9', 'short_description' => 'Cerebral infarction, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I64', 'short_description' => 'Stroke, not specified as hemorrhage or infarction', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I73.9', 'short_description' => 'Peripheral vascular disease, unspecified', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],
            ['icd_code' => 'I80.3', 'short_description' => 'Phlebitis and thrombophlebitis of lower extremities', 'category' => 'I00-I99', 'chapter' => 'Circulatory System'],

            // Chapter 10: Diseases of the respiratory system (J00-J99)
            ['icd_code' => 'J00', 'short_description' => 'Acute nasopharyngitis (common cold)', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J02.9', 'short_description' => 'Acute pharyngitis, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J03.9', 'short_description' => 'Acute tonsillitis, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J06.9', 'short_description' => 'Acute upper respiratory infection, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J18.9', 'short_description' => 'Pneumonia, unspecified organism', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J20.9', 'short_description' => 'Acute bronchitis, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J30.9', 'short_description' => 'Allergic rhinitis, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J32.9', 'short_description' => 'Chronic sinusitis, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J40', 'short_description' => 'Bronchitis, not specified as acute or chronic', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J44.9', 'short_description' => 'Chronic obstructive pulmonary disease, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],
            ['icd_code' => 'J45.9', 'short_description' => 'Asthma, unspecified', 'category' => 'J00-J99', 'chapter' => 'Respiratory System'],

            // Chapter 11: Diseases of the digestive system (K00-K95)
            ['icd_code' => 'K21.0', 'short_description' => 'Gastro-esophageal reflux disease with esophagitis', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K25.9', 'short_description' => 'Gastric ulcer, unspecified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K29.7', 'short_description' => 'Gastritis, unspecified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K30', 'short_description' => 'Functional dyspepsia', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K35.8', 'short_description' => 'Acute appendicitis, other and unspecified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K40.9', 'short_description' => 'Inguinal hernia, unspecified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K52.9', 'short_description' => 'Noninfective gastroenteritis and colitis, unspecified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K59.0', 'short_description' => 'Constipation', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K76.0', 'short_description' => 'Fatty liver, not elsewhere classified', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],
            ['icd_code' => 'K80.2', 'short_description' => 'Calculus of gallbladder without cholecystitis', 'category' => 'K00-K95', 'chapter' => 'Digestive System'],

            // Chapter 12: Diseases of the skin (L00-L99)
            ['icd_code' => 'L20.9', 'short_description' => 'Atopic dermatitis, unspecified', 'category' => 'L00-L99', 'chapter' => 'Skin Diseases'],
            ['icd_code' => 'L30.9', 'short_description' => 'Dermatitis, unspecified', 'category' => 'L00-L99', 'chapter' => 'Skin Diseases'],
            ['icd_code' => 'L50.9', 'short_description' => 'Urticaria, unspecified', 'category' => 'L00-L99', 'chapter' => 'Skin Diseases'],

            // Chapter 13: Diseases of the musculoskeletal system (M00-M99)
            ['icd_code' => 'M15.9', 'short_description' => 'Polyosteoarthritis, unspecified', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M17.9', 'short_description' => 'Osteoarthritis of knee, unspecified', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M25.5', 'short_description' => 'Pain in joint', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M54.5', 'short_description' => 'Low back pain', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M54.2', 'short_description' => 'Cervicalgia', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M79.3', 'short_description' => 'Panniculitis, unspecified', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],
            ['icd_code' => 'M81.0', 'short_description' => 'Age-related osteoporosis without current pathological fracture', 'category' => 'M00-M99', 'chapter' => 'Musculoskeletal'],

            // Chapter 14: Diseases of the genitourinary system (N00-N99)
            ['icd_code' => 'N18.9', 'short_description' => 'Chronic kidney disease, unspecified', 'category' => 'N00-N99', 'chapter' => 'Genitourinary'],
            ['icd_code' => 'N20.0', 'short_description' => 'Calculus of kidney', 'category' => 'N00-N99', 'chapter' => 'Genitourinary'],
            ['icd_code' => 'N39.0', 'short_description' => 'Urinary tract infection, site not specified', 'category' => 'N00-N99', 'chapter' => 'Genitourinary'],
            ['icd_code' => 'N40.0', 'short_description' => 'Benign prostatic hyperplasia without lower urinary tract symptoms', 'category' => 'N00-N99', 'chapter' => 'Genitourinary'],
            ['icd_code' => 'N76.0', 'short_description' => 'Acute vaginitis', 'category' => 'N00-N99', 'chapter' => 'Genitourinary'],

            // Chapter 15: Pregnancy, childbirth and puerperium (O00-O9A)
            ['icd_code' => 'O80', 'short_description' => 'Encounter for full-term uncomplicated delivery', 'category' => 'O00-O9A', 'chapter' => 'Pregnancy'],
            ['icd_code' => 'O21.0', 'short_description' => 'Mild hyperemesis gravidarum', 'category' => 'O00-O9A', 'chapter' => 'Pregnancy'],

            // Chapter 16: Certain conditions originating in perinatal period (P00-P96)
            ['icd_code' => 'P07.3', 'short_description' => 'Preterm newborn', 'category' => 'P00-P96', 'chapter' => 'Perinatal'],
            ['icd_code' => 'P59.9', 'short_description' => 'Neonatal jaundice, unspecified', 'category' => 'P00-P96', 'chapter' => 'Perinatal'],

            // Chapter 18: Symptoms, signs and abnormal findings (R00-R99)
            ['icd_code' => 'R05', 'short_description' => 'Cough', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R06.0', 'short_description' => 'Dyspnea', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R07.9', 'short_description' => 'Chest pain, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R10.9', 'short_description' => 'Abdominal pain, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R11.2', 'short_description' => 'Nausea with vomiting, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R19.7', 'short_description' => 'Diarrhea, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R42', 'short_description' => 'Dizziness and giddiness', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R50.9', 'short_description' => 'Fever, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R51', 'short_description' => 'Headache', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R53.83', 'short_description' => 'Other fatigue', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R55', 'short_description' => 'Syncope and collapse', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],
            ['icd_code' => 'R73.9', 'short_description' => 'Hyperglycemia, unspecified', 'category' => 'R00-R99', 'chapter' => 'Symptoms/Signs'],

            // Chapter 19: Injury, poisoning (S00-T88)
            ['icd_code' => 'S00.9', 'short_description' => 'Unspecified superficial injury of head', 'category' => 'S00-T88', 'chapter' => 'Injury/Poisoning'],
            ['icd_code' => 'S06.0', 'short_description' => 'Concussion', 'category' => 'S00-T88', 'chapter' => 'Injury/Poisoning'],
            ['icd_code' => 'S52.9', 'short_description' => 'Unspecified fracture of forearm', 'category' => 'S00-T88', 'chapter' => 'Injury/Poisoning'],
            ['icd_code' => 'S82.9', 'short_description' => 'Unspecified fracture of lower leg', 'category' => 'S00-T88', 'chapter' => 'Injury/Poisoning'],
            ['icd_code' => 'T78.4', 'short_description' => 'Allergy, unspecified', 'category' => 'S00-T88', 'chapter' => 'Injury/Poisoning'],

            // Chapter 21: Factors influencing health status (Z00-Z99)
            ['icd_code' => 'Z00.0', 'short_description' => 'Encounter for general adult medical examination', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
            ['icd_code' => 'Z23', 'short_description' => 'Encounter for immunization', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
            ['icd_code' => 'Z34.9', 'short_description' => 'Encounter for supervision of normal pregnancy, unspecified', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
            ['icd_code' => 'Z71.3', 'short_description' => 'Dietary counseling and surveillance', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
            ['icd_code' => 'Z87.8', 'short_description' => 'Personal history of other specified conditions', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
            ['icd_code' => 'Z96.1', 'short_description' => 'Presence of intraocular lens', 'category' => 'Z00-Z99', 'chapter' => 'Health Status'],
        ];

        foreach ($codes as $code) {
            DB::table('icd_codes')->insert([
                'icd_code' => $code['icd_code'],
                'short_description' => $code['short_description'],
                'long_description' => $code['short_description'],
                'icd_version' => 'ICD-10',
                'category' => $code['category'],
                'chapter' => $code['chapter'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('ICD Code Seeder completed: Created ' . count($codes) . ' ICD-10 codes.');
    }
}
