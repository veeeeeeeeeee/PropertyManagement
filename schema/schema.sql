create table property_type (
  type_id number not null,
  type_name varchar2(30) not null,
  constraint type_pk primary key (type_id)
);

create sequence type_seq
start with 1;

create table property (
  prop_id number not null,
  prop_street varchar2(100) not null,
  prop_suburb varchar2(50) not null,
  prop_state varchar2(5) not null,
  prop_pc varchar2(6) not null,
  prop_type number not null,
  constraint prop_pk primary key (prop_id),
  constraint type_fk foreign key (prop_type) references property_type (type_id)
);

create sequence prop_seq
start with 1;

alter table property
add prop_desc long;

alter table property
add price number;

create table feature (
  feat_id number not null,
  feat_name varchar(255) not null,
  constraint feat_pk primary key (feat_id)
);

create table property_feature (
  prop_id number not null,
  feat_id number not null,
  no_feat number not null,
  constraint prop_feat_pk primary key (prop_id, feat_id),
  constraint prop_fk1 foreign key (prop_id) references property(prop_id),
  constraint feat_fk2 foreign key (feat_id) references feature(feat_id)
);

create sequence feat_seq
start with 1;

create table client (
  client_id number not null,
  client_fname varchar(255) not null,
  client_lname varchar(255) not null,
  client_email varchar(100),
  client_mobile varchar(20),
  client_street varchar(255),
  client_suburb varchar(100),
  client_state varchar(3),
  client_pc varchar(4)
);

create sequence client_seq
start with 1;

create table property_image (
  img_id number not null,
  img_path varchar2(255) not null,
  prop_id number not null,
  constraint img_pk primary key (img_id),
  constraint prop_fk foreign key (prop_id) references property (prop_id)
);

create sequence prop_img_seq
start with 1;

create or replace trigger property_delete_trigger 
before delete on property 
for each row 
begin
  delete from property_image where prop_id = :old.prop_id;
  delete from property_feature where prop_id = :old.prop_id;
end;