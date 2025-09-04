CREATE TABLE imapsync (
    id integer NOT NULL,
    mailbox character varying(255) DEFAULT ''::character varying NOT NULL,
    active BOOLEAN DEFAULT 't' NOT NULL,
    src_server character varying(255) DEFAULT ''::character varying NOT NULL,
    src_auth character varying(15) DEFAULT 'password'::character varying NOT NULL,
    src_user character varying(255) DEFAULT ''::character varying NOT NULL,
    src_password character varying(255) DEFAULT ''::character varying NOT NULL,
    src_folder character varying(255) DEFAULT ''::character varying NOT NULL,
    dest_password character varying(255) DEFAULT ''::character varying NOT NULL,
    poll_time integer DEFAULT 10 NOT NULL,
    fetchall BOOLEAN DEFAULT 'f' NOT NULL,
    keep BOOLEAN DEFAULT 't' NOT NULL,
    protocol character varying(15) DEFAULT 'IMAP'::character varying NOT NULL,
    subscribeall BOOLEAN DEFAULT 't' NOT NULL,
    skipempty BOOLEAN DEFAULT 't' NOT NULL,
    maxage integer DEFAULT 365 NOT NULL,
    usessl BOOLEAN DEFAULT 't' NOT NULL,
    sslcertck BOOLEAN DEFAULT 'f' NOT NULL,
    sslcertpath character varying(255) DEFAULT ''::character varying,
    sslfingerprint character varying(255) DEFAULT ''::character varying,
    extra_options text,
    returned_text text,
    mda character varying(255) DEFAULT ''::character varying NOT NULL,
    date timestamp with time zone DEFAULT now() NOT NULL
    CONSTRAINT imapsync_protocol_check CHECK (((protocol)::text = ANY (ARRAY[('POP3'::character varying)::text, ('IMAP'::character varying)::text, ('POP2'::character varying)::text, ('ETRN'::character varying)::text, ('AUTO'::character varying)::text]))),
    CONSTRAINT imapsync_src_auth_check CHECK (((src_auth)::text = ANY (ARRAY[('password'::character varying)::text, ('kerberos_v5'::character varying)::text, ('kerberos'::character varying)::text, ('kerberos_v4'::character varying)::text, ('gssapi'::character varying)::text, ('cram-md5'::character varying)::text, ('otp'::character varying)::text, ('ntlm'::character varying)::text, ('msn'::character varying)::text, ('ssh'::character varying)::text, ('any'::character varying)::text])))
);

CREATE SEQUENCE imapsync_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
	
ALTER SEQUENCE imapsync_id_seq OWNED BY imapsync.id;

ALTER TABLE ONLY imapsync ALTER COLUMN id SET DEFAULT nextval('imapsync_id_seq'::regclass);

ALTER TABLE ONLY imapsync ADD CONSTRAINT imapsync_pkey PRIMARY KEY (id);
