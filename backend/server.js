const express=require('express');
const mysql=require('mysql');
const cors=require('cors');
const multer=require('multer');
const bcrypt=require('bcryptjs');
const path=require('path');

const app=express();

//middlewares
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({extended:true}));

//Mysql connection pool
const db=mysql.createPool({
    host:'localhost',
    user:'root',
    password:'',
    database:'research_portal'
});

//connecting to mysql
db.getConncection()
                .then(()=>console.log('Connected to MySQL'))
                .catch((err)=>console.log("Error connecting to MySQL:", err.message));

//multer setup for (cv file upload)
const storage=multer.diskStorage({
    destination:(req,file,cb)=>{
        cb(null,'uploads/'); //file will be stored in uploads folder
    },
    filename:(req,file,cb)=>{
        cb(null,Date.now()+path.extname(file.originalname)); //file name will be current timestamp + original file extension
    }
});
const upload=multer({storage:storage});