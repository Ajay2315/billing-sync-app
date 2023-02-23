<script>
    import axios from "axios";
    import { onMounted, ref } from "vue"

    export default {
        setup() {
            var current = new Date();
            current.setDate(current.getDate() - 1);
            current = current.toISOString().substring(0, 10);
            let status = ref('System is ready to sync to PAMANA Server'), 
                resData = ref([]), isSyncing = ref(true),
                isOnline = ref(false),
                timeoutId = null;
            let xType = ref(''), xText = ref(''), errors = ref(false)
            var saveButton = 'Rebuild',
                savingButton = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
            let saveButtonText = ref(saveButton), isRebuilding = ref(false), rebuildStatus = ref(''),
                form = ref({
                    masterkey: '',
                    dateFrom: current,
                    dateTo: current
                })

            // onMounted(() => {
            //     uploadPaymentToHO();
            //     isOnline.value = navigator.onLine
            // })

            const uploadPaymentToHO = async () => {
                isOnline.value = navigator.onLine
                clearTimeout(timeoutId);
                resData.value = '';
                isSyncing.value = true
                status.value = 'Syncing data ... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                await axios.get('/api/SyncPaymentToHO')
                .then((response) => {
                    status.value = 'Done Syncing... Cycle will repeat after 1 minute.';
                    resData.value = response.data;
                    timeoutId = setTimeout(uploadPaymentToHO, 60000);
                    isSyncing.value = false
                })
                .catch((error) => {
                    status.value = 'Error Inserting Data.';
                    resData.value = error.response.data;
                    timeoutId = setTimeout(uploadPaymentToHO, 60000);
                    isSyncing.value = false
                });
            }
                
            const rebuildConfiramtion = (type, text) => {
                errors.value = []
                masterkey.value = ''
                xType.value = type
                xText.value = text
                $('#rebuildModal').modal('show');
            }

            const rebuild = async () => {
                errors.value = []
                saveButtonText.value = savingButton
                isRebuilding.value = true
                rebuildStatus.value = `Processing please don't close/reload...`

                var url = ''
                if(xType.value == 'PAYMENTSWATER') {
                    url = '/api/rebuildPaymentsWater'
                }else if(xType.value == 'PAYMENTSOTHERS') {
                    url = '/api/rebuildPaymentsOthers'
                }else if(xType.value == 'READINGBILLS') {
                    url = '/api/rebuildReading'
                }else{
                    return;
                }

                const formData = new FormData();
                formData.append('masterkey', form.value.masterkey)
                formData.append('dateFrom', form.value.dateFrom)
                formData.append('dateTo', form.value.dateTo)
                await axios.post(url, formData)
                .then((response) => {
                    $('#rebuildModal').modal('hide');
                    toast.fire({
                        icon: 'success',
                        title: 'Data will be reuploaded on the next sync.'
                    })
                })
                .catch((error) => {
                    errors.value = error.response.data
                    saveButtonText.value = saveButton
                    isRebuilding.value = false
                    rebuildStatus.value = ''
                    masterkey.value = ''
                    toast.fire({
                        icon: 'error',
                        title: 'Validation Failed.'
                    })
                });
            }

            return {
                status, resData, isOnline, rebuildConfiramtion,
                xType, errors, saveButtonText, rebuild,
                isRebuilding, rebuildStatus, form, xText, isSyncing
            }
        }
    }
</script>

<template>

<main class="main" id="main">
    <section class="section">
      <div class="alert alert-danger">
        <h4 class="pt-1"><strong> Hayaan lamang na naka-open ito para mai-upload ang data ng district sa Head Office. Salamat po!</strong></h4>
        <small>-IT Team</small>
      </div>
      <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="card-title">
                        Re-upload data to head office
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="col-md-12">
                                <div class="row mb-3"> 
                                    <label for="inputDate" class="col-sm-4 col-form-label">Date From</label>
                                    <div class="col-sm-8"> 
                                        <input type="date" name="dateFrom" class="form-control" v-model="form.dateFrom">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="row mb-3"> 
                                    <label for="inputDate" class="col-sm-4 col-form-label">Date To</label>
                                    <div class="col-sm-8"> 
                                        <input type="date" name="dateTo" class="form-control" v-model="form.dateTo">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="dropdown">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    Reupload Action
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" @click.prevent="rebuildConfiramtion('PAYMENTSWATER', 'WATER BILL PAYMENTS')">PAYMENTS WATER BILL</a></li>
                                    <li><a class="dropdown-item" href="#" @click.prevent="rebuildConfiramtion('PAYMENTSOTHERS', 'OTHER PAYMENTS')">PAYMENTS OTHERS</a></li>
                                    <li><a class="dropdown-item" href="#" @click.prevent="rebuildConfiramtion('READINGBILLS', 'READING BILLS')">READING BILLS</a></li>
                                    <li><a class="dropdown-item" href="#" @click.prevent="rebuildConfiramtion('RESERVED', 'RESERVED')">RESERVED</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
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

    <div class="modal fade mt-8" id="rebuildModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="rebuildModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="alert alert-info alert-dismissible fade show text-center" role="alert" v-if="isSyncing"> 
                        Rebuild is unavalable during the syncing process. Please wait for the sync to finish in a few moments.
                    </div>
                    <div class="col-12 mb-3">
                        <p class="text-center"> Please type the MASTERKEY to rebuild </p>
                        <h3 class="text-center"><strong class="text-danger"><em>{{ xText }}</em></strong></h3>
                        <input type="password" name="masterkey" v-model="form.masterkey" class="form-control col-sm-8 text-center" :class="{'is-invalid': errors.masterkey}" id="masterkey" @keydown.enter="rebuild()" autocomplete="off" required>
                        <div class="invalid-feedback text-start" v-if="errors.masterkey">{{ errors.masterkey[0] }}</div>
                        <small class="text-danger text-start">{{ rebuildStatus }}</small>
                        <small class="text-danger text-start" v-if="errors.date">{{ errors.date[0] }}</small>
                    </div>
                    <button type="button" class="btn btn-success float-end ml-2 w-90px" @click="rebuild()" :disabled="isRebuilding || isSyncing"><span v-html="saveButtonText"></span></button>
                    <button type="button" class="btn btn-white float-end " data-bs-dismiss="modal" :disabled="isRebuilding">Close</button>

                </div>
            </div>
        </div>
    </div>
</main>
</template>