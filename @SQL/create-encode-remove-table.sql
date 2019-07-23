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




--