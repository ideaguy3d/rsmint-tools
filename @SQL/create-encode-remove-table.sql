USE RSMint_1
GO

CREATE TABLE RemovedEncodes
(
    id            INT IDENTITY (1,1) NOT NULL PRIMARY KEY,
    created_at    DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rsm_file_name VARCHAR(512)       NOT NULL,
    rsm_row       INT                NOT NULL,
    rsm_column    INT                NOT NULL
)
GO

-- add a column
ALTER TABLE [RSMint_1].[dbo].[RemovedEncodes] ADD first_field VARCHAR(512)
GO

-- forgot to create a column for the actual encoded char
ALTER TABLE [RSMint_1].[dbo].[RemovedEncodes] ADD encode VARCHAR(2)
GO

ALTER TABLE [RSMint_1].[dbo].[RemovedEncodes] ADD encode2 VARCHAR(2048)
GO

-- DROP COLUMN
ALTER TABLE [RSMint_1].[dbo].[RemovedEncodes]
DROP COLUMN [encode]
GO

-- create encode tracker table
CREATE TABLE EncodeTracker
(
    id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    angularjs_id VARCHAR(256) NOT NULL,
    char_count INT NULL,
    status VARCHAR(256) NULL
)
GO





--