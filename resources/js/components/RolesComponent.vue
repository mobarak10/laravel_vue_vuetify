<template>
    <v-app>
        <v-data-table 
        :loading="loading"
        class="elevation-1" 
        item-key="name" 
        loading-text="Loading... Please wait"
        :headers="headers"
        @pagination="paginate"
        :items="roles.data"
        :server-items-length="roles.total"
        :items-per-page = 5
        sort-by="calories"
        :footer-props="{
            itemsPerPageOptions: [5,10,15],
            itemsPerPageText: 'Roles per page',
            'show-current-page': true,
            'show-first-last-page': true,
        }"
    >
        <template v-slot:top>
            <v-toolbar flat color="dark">
                <v-toolbar-title>Role Management</v-toolbar-title>
                <v-divider
                class="mx-4"
                inset
                vertical
                >
                </v-divider>

                <v-spacer></v-spacer>
                <v-dialog v-model="dialog" max-width="500px">
                    <template v-slot:activator="{ on, attrs }">
                        <v-btn
                        color="error"
                        dark
                        class="mb-2"
                        v-bind="attrs"
                        v-on="on"
                        >Add New Role</v-btn>
                    </template>
                    
                    <v-card>
                        <v-card-title>
                            <span class="headline">{{ formTitle }}</span>
                        </v-card-title>

                        <v-card-text>
                            <v-container>
                                <v-row>
                                    <v-col cols="12" sm="12">
                                        <v-text-field color="error" v-model="editedItem.name" label="Role Name"></v-text-field>
                                    </v-col>
                                </v-row>    
                            </v-container>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="error darken-1" text @click="close">Cancel</v-btn>
                            <v-btn color="error darken-1" text @click="save">Save</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
            </v-toolbar>
        </template>

        <template v-slot:item.actions="{ item }">
            <v-icon
                small
                class="mr-2"
                @click="editItem(item)"
            >
                mdi-pencil
            </v-icon>

            <v-icon
                small
                @click="deleteItem(item)"
            >
                mdi-delete
            </v-icon>
        </template>

        <template v-slot:no-data>
            <v-btn color="primary" @click="initialize">Reset</v-btn>
        </template>
    </v-data-table>
    <v-snackbar 
        v-model="snackbar"
        >
            {{ snackbar_text }}
            <template v-slot:action="{ attrs }">
                <v-btn
                color="error"
                text
                v-bind="attrs"
                @click="snackbar = false"
                >
                Close
                </v-btn>
            </template>
        </v-snackbar>
    </v-app>
</template>

<script>
    export default {
        data: () => ({
            dialog: false,
            loading: false,
            snackbar: false,
            snackbar_text: '',
            headers: [
            {
            text: '#',
            align: 'start',
            sortable: false,
            value: 'id',
            },
            { text: 'Name', value: 'name' },
            { text: 'Created at', value: 'created_at' },
            { text: 'Updated at', value: 'updated_at' },
            { text: 'Actions', value: 'actions', sortable: false },
            ],

            roles: [],
            editedIndex: -1,    
                
            editedItem: {
                id: '',
                name: '',
                created_at: '',
                updated_at: '',
            },

            defaultItem: {
                id: '',
                name: '',
                created_at: '',
                updated_at: '',
            },
        }),

        computed: {
            formTitle () {
                return this.editedIndex === -1 ? 'New Item' : 'Edit Item'
            },
        },

        watch: {
            dialog (val) {
                val || this.close()
            },
        },

        created () {
            this.initialize()
        },

        methods: {
            paginate(e){
                console.dir(e)
                axios.get(`/api/roles?page=${e.page}`, {params:{'per_page': e.itemsPerPage}})
                .then(res=> this.roles = res.data.roles)
                .catch(err => {
                    if(err.response.status == 401)
                    this.$router.push('/login')
                    localStorage.removeItem('token')
                })
            },

            initialize () {
                // Add a request interceptor
                axios.interceptors.request.use((config) => {
                    this.loading = true; // 
                    return config;
                }, (error) => {
                    this.loading = false
                    return Promise.reject(error);
                });

                // Add a response interceptor
                axios.interceptors.response.use((response) => {
                    this.loading = false; // 
                    return response;
                }, (error) => {
                    this.loading = false
                    return Promise.reject(error);
                });
            },

            editItem (item) {
                this.editedIndex = this.roles.indexOf(item)
                this.editedItem = Object.assign({}, item)
                this.dialog = true
            },

            deleteItem (item) {
                const index = this.roles.indexOf(item)
                let decide = confirm('Are you sure you want to delete this item?')
                if(decide){
                    axios.delete('api/roles/'+item.id)
                    .then(res => {
                        this.snackbar = true
                        this.snackbar_text = "Record Deleted Succesfully!"
                        this.roles.splice(index, 1)
                    })
                    // this.close()
                    .catch(err => {
                        console.log(err.response)
                        this.snackbar = true
                        this.snackbar_text = "Something went wrong!!!"
                    })
                }
            },

            close () {
                this.dialog = false
                setTimeout(() => {
                this.editedItem = Object.assign({}, this.defaultItem)
                this.editedIndex = -1
                }, 100)
            },

            save () {
                if (this.editedIndex > -1) {
                    // console.log(this.editedItem)
                    axios.put('api/roles/'+this.editedItem.id, {
                        'name': this.editedItem.name
                    })
                    .then(res => {
                        // console.log(res.data.role)
                        const role = res.data.role // get edited item pass from controller
                        // console.log(this.roles)
                        const role_ids = this.roles.map(item => {
                            return item.id // get all id of roles array
                        })
                        // console.log(role_ids)
                        const index_of_role = role_ids.indexOf(role.id) // get edited item id
                        // console.log(index_of_role)
                        this.roles.splice(index_of_role, 1, role) // in splice method first argument is which is deleted, secound argument is number of deleted item, third argument is new insert item.
                        this.snackbar = true
                        this.snackbar_text = "Record Updated Succesfully!"
                        
                    })
                    .catch(err =>{
                            console.log(err.response)
                            this.snackbar = true
                            this.snackbar_text = "Something went wrong!!!"
                        })
                    
                    // Object.assign(this.roles[this.editedIndex], this.editedItem)
                } else {
                    axios.post('api/roles', {
                        'name': this.editedItem.name
                    })
                    .then(res => {
                        this.roles.push(res.data.role)
                        this.snackbar = true
                        this.snackbar_text = "Record Added Succesfully!"
                    })
                    .catch(err => {
                        console.log(err.response)
                        this.snackbar = true
                        this.snackbar_text = "Something went wrong!!!"
                    })
                    
                }
                this.close()
            },
        },

    }
</script>