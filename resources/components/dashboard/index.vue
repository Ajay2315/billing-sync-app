<script>
    import axios from "axios";
    import { onMounted, ref } from "vue"

    export default {
        setup() {
            let status = ref('System is ready to sync to PAMANA Server'), 
                resData = ref([]),
                isOnline = ref(false),
                timeoutId = null;

                onMounted(() => {
                    uploadPaymentToHO();
                    isOnline.value = navigator.onLine
                })

                const uploadPaymentToHO = async () => {
                    isOnline.value = navigator.onLine
                    clearTimeout(timeoutId);
                    resData.value = '';
                    status.value = 'Syncing data ... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    await axios.get('/api/SyncPaymentToHO')
                    .then((response) => {
                        status.value = 'Done Syncing... Cycle will repeat after 1 minute.';
                        resData.value = response.data;
                        timeoutId = setTimeout(uploadPaymentToHO, 60000);
                    })
                    .catch((error) => {
                        status.value = 'Error Inserting Data.';
                        resData.value = error.response.data;
                        timeoutId = setTimeout(uploadPaymentToHO, 60000);
                    });
                }

            return {
                status, resData, isOnline
            }
        }
    }
</script>

<template>

<main class="main" id="main">
    <section class="section">
      <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                Do not close this app. This will sync the district data to HO Server. Thank you!
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        Sync Status 
                        <div class="badge rounded-pill bg-success" v-if="isOnline">Online</div>
                        <div class="badge rounded-pill bg-danger" v-else>Offline</div>
                    </h5>
                    <span v-html="status"></span>
                    <div class="alert alert-success alert-dismissible fade show mt-3" :class="{'alert-danger': resData.error}" role="alert" v-if="resData.message"> 
                        <strong>Error:</strong> {{ resData.error }}
                    </div>
                    <div class="alert alert-success alert-dismissible fade show" :class="{'alert-danger': resData.error}" role="alert" v-if="resData.message"> 
                        <strong>Table:</strong> {{ resData.table }} <br>
                        <strong>Message:</strong> {{ resData.message }} <br>
                        <strong v-if="resData.error">Account:</strong> {{ resData.account }}
                    </div>

                    <div class="alert alert-success alert-dismissible fade show" :class="{'alert-danger': resData.error}" role="alert" v-if="resData.message"> 
                        <strong>cURL Response:</strong> {{ resData.result }}
                    </div>
                </div>
            </div>
        </div>
      </div>
    </section>
</main>
</template>